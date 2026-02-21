<?php

declare(strict_types=1);

namespace App\Form\Manager;

use App\Service\CoreLocatorInterface;
use App\Service\Urlizer;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * GlobalManager.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GlobalManager implements GlobalManagerInterface
{
    private ?FormInterface $form;
    private ?string $redirection;

    /**
     * GlobalManager constructor.
     */
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly CoreLocatorInterface $coreLocator,
    ) {
        $this->form = null;
        $this->redirection = null;
    }

    /**
     * To set form and process.
     */
    public function setForm(string $formClassname, mixed $entity = null, mixed $formManager = null, array $formOptions = []): void
    {
        if (!$entity) {
            throw new NotFoundHttpException($this->coreLocator->translator()->trans('This page does not exist!', [], 'back'));
        }

        $this->form = $this->formFactory->create($formClassname, $entity, $formOptions);
        $this->form->handleRequest($this->coreLocator->request());

        if ($this->form->isSubmitted() && $this->form->isValid()) {
            $entity = $this->form->getData();
            $interface = $this->coreLocator->entityInterface($entity);
            if (method_exists($entity, 'getAdminName') && method_exists($entity, 'setSlug') && !$entity->getSlug()) {
                $entity->setSlug(Urlizer::urlize($entity->getAdminName()));
            }
            if ($interface['masterField']) {
                $this->setMasterField($entity, $interface['masterField'], $interface['masterFieldSetter']);
                $this->setPosition($entity, $interface['masterField'], $interface['masterFieldGetter']);
            }
            $isNew = !$entity->getId();
            if ($isNew) {
                $entity->setCreatedBy($this->coreLocator->user());
            } else {
                $entity->setUpdatedBy($this->coreLocator->user());
            }
            if (is_object($formManager) && method_exists($formManager, 'execute')) {
                $formManager->execute($entity, $this->form);
            }
            $this->coreLocator->em()->persist($entity);
            $this->coreLocator->em()->flush();
            $this->setFlashBag($isNew);
            $this->setRedirection($interface, $entity);
        }
    }

    /**
     * To delete entity.
     */
    public function delete(mixed $entityToDelete): ?string
    {
        $session = $this->coreLocator->request()->getSession();
        $allowed = $this->coreLocator->authorizationChecker()->isGranted('ROLE_DELETE');
        $this->redirection = $this->coreLocator->request()->headers->get('referer');

        if (!is_object($entityToDelete)) {
            $session->getFlashBag()->add('error', $this->coreLocator->translator()->trans('An error has occurred!', [], 'back'));
            return $this->redirection;
        }

        if ($allowed) {
            $interface = $this->coreLocator->entityInterface($entityToDelete);
            if ($interface['name']) {
                $repository = $this->coreLocator->em()->getRepository(get_class($entityToDelete));
                $currentPosition = method_exists($entityToDelete, 'getPosition') ? $entityToDelete->getPosition() : false;
                $this->coreLocator->em()->remove($entityToDelete);
                if (is_numeric($currentPosition)) {
                    $entities = $interface['masterField'] ? $repository->findBy([$interface['masterField'] => $entityToDelete->$interface['masterFieldGetter']()]) : $repository->findAll();
                    foreach ($entities as $entity) {
                        if ($entity->getPosition() > $currentPosition) {
                            $entity->setPosition($entity->getPosition() - 1);
                            $this->coreLocator->em()->persist($entity);
                        }
                    }
                }
                $this->coreLocator->em()->flush();
                $session->getFlashBag()->add('success', $this->coreLocator->translator()->trans('Successfully deleted!', [], 'back'));
            }
        } else {
            $session->getFlashBag()->add('error', $this->coreLocator->translator()->trans('You are not allowed to delete!', [], 'back'));
        }

        return $this->redirection;
    }

    /**
     * To get form.
     */
    public function getForm(): ?FormInterface
    {
        return $this->form;
    }

    /**
     * To set a master field.
     */
    public function setMasterField(mixed $entity, string $masterField, string $masterFieldSetter): void
    {
        if ($masterFieldSetter && method_exists($entity, $masterFieldSetter) && $this->coreLocator->request()->get($masterField)) {
            $metadata = $this->coreLocator->em()->getClassMetadata(get_class($entity));
            $masterClassname = $metadata->associationMappings[$masterField]['targetEntity'];
            $masterEntity = $this->coreLocator->em()->getRepository($masterClassname)->find($this->coreLocator->request()->get($masterField));
            $entity->$masterFieldSetter($masterEntity);
        }
    }

    /**
     * To set position.
     */
    public function setPosition(mixed $entity, string $masterField, string $masterFieldGetter): void
    {
        if (method_exists($entity, 'getPosition')) {
            $repository = $this->coreLocator->em()->getRepository(get_class($entity));
            $entities = $masterField ? $repository->findBy([$masterField => $entity->$masterFieldGetter()]) : $repository->findAll();
            $position = count($entities) + 1;
            $entity->setPosition($position);
        }
    }

    /**
     * To set a flash bag.
     */
    public function setFlashBag(bool $isNew): void
    {
        $session = $this->coreLocator->request()->getSession();
        $message = $isNew ? $this->coreLocator->translator()->trans('Successfully created!', [], 'back')
            : $this->coreLocator->translator()->trans('Successfully updated!', [], 'back');
        $session->getFlashBag()->add('success', $message);
    }

    /**
     * To get redirection.
     */
    public function getRedirection(): ?string
    {
        return $this->redirection;
    }

    /**
     * To set redirection.
     */
    public function setRedirection(array $interface, mixed $entity): void
    {
        if (!empty($interface['name'])) {
            $submitName = $this->form->getClickedButton()->getName();
            $redirections = [
                'save' => $this->coreLocator->request()->headers->get('referer'),
                'saveEdit' => 'saveEdit' === $submitName ?$this->coreLocator->router()->generate('back_'.$interface['name'].'_edit', $this->coreLocator->routeArgs('back_'.$interface['name'].'_edit', $entity)) : null,
                'saveBack' => 'saveBack' === $submitName ? $this->coreLocator->router()->generate('back_'.$interface['name'].'_index', $this->coreLocator->routeArgs('back_'.$interface['name'].'_index', $entity)) : null,
            ];
            $this->redirection = !empty($redirections[$submitName]) ? $redirections[$submitName] : $redirections['save'];
        } else {
            $this->redirection = $this->coreLocator->request()->headers->get('referer');
        }
    }
}
