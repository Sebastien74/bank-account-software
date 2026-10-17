<?php

declare(strict_types=1);

namespace App\Form\Type\Wallet;

use App\Entity\Wallet;
use App\Form\Widget\SubmitType;
use App\Service\CoreLocatorInterface;
use App\Twig\CoreRuntime;
use Doctrine\ORM\EntityRepository;
use Exception;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * OperationType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class OperationType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * OperationType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly CoreRuntime $coreExtension,
    )
    {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * @throws Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /*
         * Le tiers dit presque toujours la catégorie : la liste associe à
         * chaque tiers celle que son historique montre, pour que le choix du
         * tiers la présélectionne. Elle reste modifiable — c'est une
         * proposition, pas une règle.
         */
        $usual = $this->coreLocator->em()
            ->getRepository(Wallet\Operation::class)
            ->usualSubCategoryByOutsider();

        $builder->add('outsider', EntityType::class, [
            'label_html' => true,
            'required' => false,
            'label' => $this->translator->trans('Tiers', [], 'back'),
            'attr' => [
                'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'back'),
                'data-usual-sub-categories' => json_encode($usual, JSON_THROW_ON_ERROR),
                'data-usual-target' => 'wallet_operation_subCategory',
            ],
            'class' => Wallet\Outsider::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('o')
                    ->orderBy('o.adminName', 'ASC');
            },
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
            'row_attr' => ['class' => 'col-md-6'],
        ]);

        $builder->add('adminName', Type\TextType::class, [
            'label_html' => true,
            'required' => false,
            'mapped' => false,
            'label' => $this->translator->trans('Nouveau tiers', [], 'back'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un tiers', [], 'back'),
            ],
            'row_attr' => ['class' => 'col-md-6'],
        ]);

        $builder->add('date', Type\DateType::class, [
            'label_html' => true,
            'label' => $this->translator->trans('Date', [], 'back'),
            'widget' => 'single_text',
            'format' => $this->coreExtension->formatDate($this->coreLocator->locale())->datepickerPHP,
            'html5' => false,
            'attr' => [
                'placeholder' => $this->translator->trans('Sélectionnez une date', [], 'back'),
                'class' => 'js-datepicker',
            ],
            'row_attr' => ['class' => 'col-12'],
            'constraints' => [
                new Assert\NotBlank(message: $this->translator->trans('Veuillez sélectionner une date.', [], 'back'))
            ],
        ]);

        $builder->add('amount', Type\NumberType::class, [
            'label_html' => true,
            'label' => $this->translator->trans('Montant', [], 'back'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un montant', [], 'back'),
            ],
            'row_attr' => ['class' => 'col-md-6'],
            'constraints' => [
                new Assert\NotBlank(message: $this->translator->trans('Veuillez saisir un montant.', [], 'back')),
            ],
        ]);

        $builder->add('subCategory', EntityType::class, [
            'label_html' => true,
            'label' => $this->translator->trans('Catégorie', [], 'back'),
            'attr' => [
                'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'back'),
            ],
            'class' => Wallet\SubCategory::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('c')
                    ->orderBy('c.adminName', 'ASC');
            },
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
            'row_attr' => ['class' => 'col-md-6'],
            'constraints' => [
                new Assert\NotBlank(message: $this->translator->trans('Veuillez sélectionne une catégorie.', [], 'back')),
            ],
        ]);

        /*
         * Le tiers se donne de deux façons : par la liste, ou par le champ
         * libre. Ce dernier n'est pas mappé — c'est le manager qui en tire un
         * tiers, après validation — donc le contrôle doit lire la valeur
         * soumise. Porté jusqu'ici par une contrainte de classe qui
         * interrogeait l'entité, il n'y trouvait jamais rien et refusait
         * toute saisie d'un nouveau tiers.
         */
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'validateOutsiderOrAdminName']);

        $save = new SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Wallet\Operation::class,
            'translation_domain' => 'form',
        ]);
    }

    /**
     * Exige un tiers : celui de la liste, ou celui que le champ libre nomme.
     */
    public function validateOutsiderOrAdminName(FormEvent $event): void
    {
        $form = $event->getForm();
        $operation = $event->getData();

        $hasOutsider = $operation instanceof Wallet\Operation && $operation->getOutsider() instanceof Wallet\Outsider;
        $hasAdminName = '' !== trim((string) $form->get('adminName')->getData());

        if ($hasOutsider || $hasAdminName) {
            return;
        }

        $message = $this->translator->trans('Sélectionnez un tiers existant ou saisissez un nouveau tiers.', [], 'back');
        $form->get('outsider')->addError(new FormError($message));
        $form->get('adminName')->addError(new FormError($message));
    }

    public function getBlockPrefix(): string
    {
        return 'wallet_operation';
    }
}
