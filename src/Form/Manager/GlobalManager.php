<?php

declare(strict_types=1);

namespace App\Form\Manager;

use App\Service\CoreLocatorInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\Session;
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
    public function setForm(string $formClassname, mixed $entity = null, array $options = []): void
    {
        if (!$entity) {
            throw new NotFoundHttpException($this->coreLocator->translator()->trans("Cette page n'existe pas !", [], 'admin'));
        }

        $this->form = $this->formFactory->create($formClassname, $entity, $options);
        $this->form->handleRequest($this->coreLocator->request());
        if ($this->form->isSubmitted() && $this->form->isValid()) {
            $entity = $this->form->getData();
            $repository = $this->coreLocator->em()->getRepository(get_class($entity));
            $interface = $entity && method_exists($entity, 'getInterface') ? $entity::getInterface() : [];
            $masterField = !empty($interface['masterField']) ? $interface['masterField'] : false;
            $masterFieldGetter = $masterField ? 'get'.ucfirst($masterField) : false;
            $masterFieldSetter = $masterField ? 'set'.ucfirst($masterField) : false;
            if ($masterFieldSetter && method_exists($entity, $masterFieldSetter) && $this->coreLocator->request()->get($masterField)) {
                $metadata = $this->coreLocator->em()->getClassMetadata(get_class($entity));
                $masterClassname = $metadata->associationMappings[$masterField]['targetEntity'];
                $masterEntity = $this->coreLocator->em()->getRepository($masterClassname)->find($this->coreLocator->request()->get($masterField));
                $entity->$masterFieldSetter($masterEntity);
            }
            if (method_exists($entity, 'getPosition')) {
                $entities = $masterField ? $repository->findBy([$masterField => $entity->$masterFieldGetter()]) : $repository->findAll();
                $position = count($entities) + 1;
                $entity->setPosition($position);
            }
            $this->coreLocator->em()->persist($entity);
            $this->coreLocator->em()->flush();
            $session = $this->coreLocator->request()->getSession();
            $session->getFlashBag()->add('success', $this->coreLocator->translator()->trans("Créé avec succès !", [], 'admin'));
            if (!empty($interface['name'])) {
                $submitName = $this->form->getClickedButton()->getName();
                $redirections = [
                    'save' => $this->coreLocator->request()->headers->get('referer'),
                    'saveEdit' => $this->coreLocator->router()->generate('admin_'.$interface['name'].'_edit', $this->coreLocator->routeArgs('admin_'.$interface['name'].'_edit', $entity)),
                    'saveBack' => $this->coreLocator->router()->generate('admin_'.$interface['name'].'_index', $this->coreLocator->routeArgs('admin_'.$interface['name'].'_index', $entity)),
                ];
                $this->redirection = !empty($redirections[$submitName]) ? $redirections[$submitName] : $redirections['save'];
            } else {
                $this->redirection = $this->coreLocator->request()->headers->get('referer');
            }
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
            $session->getFlashBag()->add('error', $this->coreLocator->translator()->trans("Une erreur est survenue !", [], 'admin'));
            return $this->redirection;
        }

        if ($allowed) {
            $interface = $entityToDelete && method_exists($entityToDelete, 'getInterface') ? $entityToDelete::getInterface() : [];
            $interfaceName = !empty($interface['name']) ? $interface['name'] : null;
            $masterField = !empty($interface['masterField']) ? $interface['masterField'] : null;
            if ($interfaceName) {
                $repository = $this->coreLocator->em()->getRepository(get_class($entityToDelete));
                $currentPosition = method_exists($entityToDelete, 'getPosition') ? $entityToDelete->getPosition() : false;
                $this->coreLocator->em()->remove($entityToDelete);
                if (is_numeric($currentPosition)) {
                    $masterFieldGetter = $masterField ? 'get'.ucfirst($masterField) : false;
                    $entities = $masterField ? $repository->findBy([$masterField => $entityToDelete->$masterFieldGetter()]) : $repository->findAll();
                    foreach ($entities as $entity) {
                        if ($entity->getPosition() > $currentPosition) {
                            $entity->setPosition($entity->getPosition() - 1);
                            $this->coreLocator->em()->persist($entity);
                        }
                    }
                }
                $this->coreLocator->em()->flush();
                $session->getFlashBag()->add('success', $this->coreLocator->translator()->trans("Supprimé avec succès !", [], 'admin'));
            }
        } else {
            $session->getFlashBag()->add('error', $this->coreLocator->translator()->trans("Vous n'êtes pas autorisé à supprimer !", [], 'admin'));
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
     * To get redirection.
     */
    public function getRedirection(): ?string
    {
        return $this->redirection;
    }
}