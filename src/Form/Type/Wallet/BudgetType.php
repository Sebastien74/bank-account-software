<?php

declare(strict_types=1);

namespace App\Form\Type\Wallet;

use App\Entity\Wallet\Budget;
use App\Entity\Wallet\SubCategory;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Service\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * BudgetType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BudgetType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * BudgetType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $builder->add('adminName', Type\TextType::class, [
            'label_html' => true,
            'label' => $this->translator->trans('Intitulé', [], 'back'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un intitulé', [], 'back'),
            ],
            'constraints' => [
                new Assert\NotBlank(message: $this->translator->trans('Veuillez saisir un initulé.', [], 'back'))
            ],
            'row_attr' => ['class' => 'col-md-6'],
        ]);

        $builder->add('amount', Type\NumberType::class, [
            'label_html' => true,
            'label' => $this->translator->trans('Montant mensuel', [], 'back'),
            'html5' => false,
            'scale' => 2,
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un montant', [], 'back'),
            ],
            'row_attr' => ['class' => 'col-md-6'],
            'constraints' => [
                new Assert\NotBlank(message: $this->translator->trans('Veuillez saisir un montant.', [], 'back')),
                new Assert\PositiveOrZero(message: $this->translator->trans('Le montant ne peut pas être négatif.', [], 'back')),
            ],
        ]);

        /*
         * Plusieurs postes par enveloppe : un arbitrage ne suit pas toujours le
         * decoupage des categories. Sans poste associe, l'enveloppe reste dans
         * la liste mais ne peut etre confrontee a aucune depense, et le tableau
         * de bord l'ignore.
         */
        $builder->add('subCategories', EntityType::class, [
            'label_html' => true,
            'required' => false,
            'multiple' => true,
            'expanded' => false,
            'label' => $this->translator->trans('Sous-postes couverts', [], 'back'),
            'attr' => [
                'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'back'),
            ],
            'class' => SubCategory::class,
            'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('sc')
                ->join('sc.category', 'c')
                ->addSelect('c')
                ->andWhere('sc.type = :type')
                ->setParameter('type', 'expenses')
                ->orderBy('c.adminName', 'ASC')
                ->addOrderBy('sc.adminName', 'ASC'),
            'choice_label' => fn ($entity) => strip_tags((string) $entity->getAdminName()),
            // Trente-deux sous-postes a plat ne se choisissent pas : le nom de
            // la categorie sert de rubrique dans la liste.
            'group_by' => fn ($entity) => strip_tags((string) $entity->getCategory()?->getAdminName()),
            'row_attr' => ['class' => 'col-12'],
        ]);

        $builder->add('saving', Type\CheckboxType::class, [
            'label_html' => true,
            'required' => false,
            'label' => $this->translator->trans("Mise de côté : l'argent sort du compte mais n'est pas dépensé", [], 'back'),
            'row_attr' => ['class' => 'col-12'],
        ]);

        $builder->add('active', Type\CheckboxType::class, [
            'label_html' => true,
            'required' => false,
            'label' => $this->translator->trans('Compter cette enveloppe dans le total', [], 'back'),
            'row_attr' => ['class' => 'col-12'],
        ]);

        if (!$isNew) {
            $builder->add('saveBack', Type\SubmitType::class, [
                'label' => $this->translator->trans('Enregistrer et retourner aux opérations', [], 'back'),
                'attr' => [
                    'class' => 'btn-label-primary',
                ],
                'row_attr' => ['class' => 'ms-2'],
            ]);
        }

        $builder->add('save', Type\SubmitType::class, [
            'label' => $this->translator->trans('Enregistrer', [], 'back'),
            'attr' => [
                'class' => 'btn-primary ms-2',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Budget::class,
            'translation_domain' => 'form',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'wallet_budget';
    }
}