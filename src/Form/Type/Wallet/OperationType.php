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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
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
        $isNew = !$builder->getData()->getId();

        $builder->add('outsider', EntityType::class, [
            'label_html' => true,
            'required' => false,
            'label' => $this->translator->trans('Tiers', [], 'back'),
            'attr' => [
                'data-placeholder' => $this->translator->trans('Sélectionnez', [], 'back'),
            ],
            'class' => Wallet\Outsider::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('o')
                    ->orderBy('o.adminName', 'ASC');
            },
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
            'row_attr' => ['class' => $isNew ? 'col-lg-6' : 'col-lg-5'],
        ]);

        $builder->add('adminName', Type\TextType::class, [
            'label_html' => true,
            'required' => false,
            'mapped' => false,
            'label' => $this->translator->trans('Nouveau tiers', [], 'back'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un tiers', [], 'back'),
            ],
            'row_attr' => ['class' => $isNew ? 'col-lg-6' : 'col-lg-7'],
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
            'row_attr' => ['class' => $isNew ? 'col-12' : 'col-lg-9'],
            'constraints' => [
                new Assert\NotBlank(['message' => $this->translator->trans('Veuillez sélectionner une date.', [], 'back')])
            ],
        ]);

        $builder->add('amount', Type\NumberType::class, [
            'label_html' => true,
            'label' => $this->translator->trans('Montant', [], 'back'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un montant', [], 'back'),
            ],
            'row_attr' => ['class' => $isNew ? 'col-12' : 'col-lg-9'],
            'constraints' => [
                new Assert\NotBlank(['message' => $this->translator->trans('Veuillez saisir un montant.', [], 'back'),]),
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
            'constraints' => [
                new Assert\NotBlank(['message' => $this->translator->trans('Veuillez sélectionne une catégorie.', [], 'back'),]),
            ],
        ]);

        $save = new SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Wallet\Operation::class,
            'translation_domain' => 'form',
            'constraints' => [
                new Assert\Callback([$this, 'validateOutsiderOrAdminName']),
            ],
        ]);
    }

    /**
     * Ensure at least one of "outsider" or "adminName" is provided.
     */
    public function validateOutsiderOrAdminName(mixed $data, ExecutionContextInterface $context): void
    {
        if (!$data instanceof Wallet\Operation) {
            return;
        }

        $hasOutsider  = null !== $data->getOutsider();
        $hasAdminName = null !== $data->getAdminName() && trim((string) $data->getAdminName()) !== '';

        if (!$hasOutsider && !$hasAdminName) {
            $message = $this->translator->trans('Sélectionnez un tiers existant ou saisissez un nouveau tiers.', [], 'back');
            $context->buildViolation($message)->atPath('outsider')->addViolation();
            $context->buildViolation($message)->atPath('adminName')->addViolation();
        }
    }

    public function getBlockPrefix(): string
    {
        return 'wallet_operation';
    }
}
