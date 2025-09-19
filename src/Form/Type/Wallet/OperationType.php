<?php

declare(strict_types=1);

namespace App\Form\Type\Wallet;

use App\Entity\Wallet\Operation;
use App\Service\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
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
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $builder->add('adminName', Type\TextType::class, [
            'label' => $this->translator->trans('Intitulé', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un intitulé', [], 'admin'),
            ],
            'constraints' => [new Assert\NotBlank([
                'message' => $this->translator->trans('Veuillez saisir un initulé.', [], 'admin'),
            ])],
            'row_attr' => ['class' => $isNew ? 'col-12' : 'col-lg-9'],
        ]);

        if (!$isNew) {
            $builder->add('saveBack', Type\SubmitType::class, [
                'label' => $this->translator->trans('Enregistrer et retourner aux opérations', [], 'admin'),
                'attr' => [
                    'class' => 'btn-dark',
                ],
                'row_attr' => ['class' => 'ms-2'],
            ]);
        }

        $builder->add('save', Type\SubmitType::class, [
            'label' => $this->translator->trans('Enregistrer', [], 'admin'),
            'attr' => [
                'class' => 'btn-secondary ms-2',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Operation::class,
            'translation_domain' => 'form',
        ]);
    }
}