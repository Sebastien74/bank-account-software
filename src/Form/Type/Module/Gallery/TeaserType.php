<?php

declare(strict_types=1);

namespace App\Form\Type\Module\Gallery;

use App\Entity\Module\Gallery\Category;
use App\Entity\Module\Gallery\Teaser;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * TeaserType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class TeaserType extends AbstractType
{
    private TranslatorInterface $translator;
    private bool $isInternalUser;

    /**
     * TeaserType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
        $this->translator = $this->coreLocator->translator();
        $user = !empty($this->tokenStorage->getToken()) ? $this->tokenStorage->getToken()->getUser() : null;
        $this->isInternalUser = $user && in_array('ROLE_INTERNAL', $user->getRoles());
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $adminNameGroup = 'col-12';
        if (!$isNew && $this->isInternalUser) {
            $adminNameGroup = 'col-md-4';
        } elseif (!$isNew) {
            $adminNameGroup = 'col-md-6';
        }

        $adminName = new WidgetType\AdminNameType($this->coreLocator);
        $adminName->add($builder, [
            'adminNameGroup' => $adminNameGroup,
            'slugGroup' => 'col-sm-2',
            'slug-internal' => $this->isInternalUser,
        ]);

        if (!$isNew) {
            if ($this->isInternalUser) {
                $builder->add('nbrItems', Type\IntegerType::class, [
                    'label' => $this->translator->trans("Nombre d'images par teaser", [], 'admin'),
                    'attr' => [
                        'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                        'group' => 'col-md-3',
                        'data-config' => true,
                    ],
                ]);

                $builder->add('itemsPerSlide', Type\IntegerType::class, [
                    'required' => false,
                    'label' => $this->translator->trans("Nombre d'images par slide", [], 'admin'),
                    'attr' => [
                        'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                        'group' => 'col-md-3',
                        'data-config' => true,
                    ],
                ]);

                $builder->add('template', Type\ChoiceType::class, [
                    'label' => $this->translator->trans('Affichage', [], 'admin'),
                    'display' => 'search',
                    'choices' => [
                        $this->translator->trans('Liste', [], 'admin') => 'list',
                        $this->translator->trans('Slider', [], 'admin') => 'slider',
                    ],
                    'attr' => ['group' => 'col-md-3', 'data-config' => true],
                ]);
            }

            $builder->add('categories', EntityType::class, [
                'label' => $this->translator->trans('Catégories', [], 'admin'),
                'required' => false,
                'display' => 'search',
                'class' => Category::class,
                'attr' => [
                    'group' => 'col-md-6',
                    'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'admin'),
                ],
                'choice_label' => function ($entity) {
                    return strip_tags($entity->getAdminName());
                },
                'multiple' => true,
            ]);

            $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
            $intls->add($builder, [
                'website' => $options['website'],
                'title_force' => true,
                'fields' => ['title', 'targetPage' => 'col-md-4', 'targetLabel' => 'col-md-4', 'targetStyle' => 'col-md-4'],
                'excludes_fields' => ['newTab', 'externalLink'],
                'label_fields' => [
                    'targetPage' => $this->translator->trans('Page de la galerie', [], 'admin'),
                ],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Teaser::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
