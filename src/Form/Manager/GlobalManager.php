<?php

declare(strict_types=1);

namespace App\Form\Manager;

use App\Service\CoreLocatorInterface;
use App\Service\Urlizer;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

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
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
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
        $request = $this->coreLocator->request();
        $session = $request->getSession();
        $allowed = $this->coreLocator->authorizationChecker()->isGranted('ROLE_DELETE');
        // `redirect()` exige une chaîne : un referer absent levait un TypeError.
        $this->redirection = $request->headers->get('referer') ?: '/';

        if (!is_object($entityToDelete)) {
            $session->getFlashBag()->add('error', $this->coreLocator->translator()->trans('An error has occurred!', [], 'back'));

            return $this->redirection;
        }

        /*
         * La suppression se faisait par un lien GET sans jeton. SameSite=lax
         * laisse passer le cookie de session sur une navigation GET de premier
         * niveau : un lien depuis une page externe suffisait donc à supprimer
         * un enregistrement. Le contrôle est posé ici plutôt que dans chacun
         * des huit contrôleurs, pour qu'il ne puisse pas être oublié au
         * prochain module.
         *
         * Il refuse par défaut : une entité sans `getId()` n'a pas de jeton
         * calculable, donc elle ne passe pas. L'inverse aurait laissé entrer
         * sans contrôle le module que ce garde-fou prétend protéger.
         *
         * Le nom du jeton porte l'interface en plus de l'identifiant : sans
         * elle, `delete-5` vaut aussi bien pour un compte que pour une
         * opération.
         */
        if (!method_exists($entityToDelete, 'getId')) {
            throw new AccessDeniedHttpException($this->coreLocator->translator()->trans('Invalid CSRF token.', [], 'back'));
        }

        $interface = $this->coreLocator->entityInterface($entityToDelete);
        $token = new CsrfToken(
            'delete-'.($interface['name'] ?? '').'-'.$entityToDelete->getId(),
            (string) $request->request->get('_token'),
        );
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            throw new AccessDeniedHttpException($this->coreLocator->translator()->trans('Invalid CSRF token.', [], 'back'));
        }

        if ($allowed) {
            if ($interface['name']) {
                $repository = $this->coreLocator->em()->getRepository(get_class($entityToDelete));
                $currentPosition = method_exists($entityToDelete, 'getPosition') ? $entityToDelete->getPosition() : false;
                $this->coreLocator->em()->remove($entityToDelete);
                if (is_numeric($currentPosition)) {
                    /*
                     * Les accolades ne sont pas decoratives.
                     *
                     * Depuis PHP 7, `$objet->$tableau['cle']()` se lit
                     * `($objet->$tableau)['cle']()` : on cherchait une
                     * propriete dont le nom etait un tableau, et toute
                     * suppression d'une entite ayant un `masterField` --
                     * c'est-a-dire toutes les operations -- levait une erreur
                     * avant d'avoir rien renumerote.
                     */
                    $getter = $interface['masterFieldGetter'];
                    $master = $interface['masterField'] && $getter && method_exists($entityToDelete, $getter)
                        ? $entityToDelete->{$getter}()
                        : null;

                    /*
                     * Seules les lignes situees apres celle qu'on retire sont
                     * chargees. Les prendre toutes hydratait les deux mille
                     * operations du compte a chaque suppression d'une seule.
                     */
                    $builder = $repository->createQueryBuilder('e')
                        ->andWhere('e.position > :position')
                        ->setParameter('position', $currentPosition);
                    if (null !== $master) {
                        $builder->andWhere(sprintf('e.%s = :master', $interface['masterField']))
                            ->setParameter('master', $master);
                    }

                    foreach ($builder->getQuery()->getResult() as $entity) {
                        $entity->setPosition($entity->getPosition() - 1);
                        $this->coreLocator->em()->persist($entity);
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
        if ($masterFieldSetter && method_exists($entity, $masterFieldSetter) && $this->coreLocator->request()->attributes->get($masterField)) {
            $metadata = $this->coreLocator->em()->getClassMetadata(get_class($entity));
            $masterClassname = $metadata->associationMappings[$masterField]['targetEntity'];
            $masterEntity = $this->coreLocator->em()->getRepository($masterClassname)->find($this->coreLocator->request()->attributes->get($masterField));
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
