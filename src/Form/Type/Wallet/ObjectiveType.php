<?php

declare(strict_types=1);

namespace App\Form\Type\Wallet;

use App\Entity\Wallet\Objective;
use App\Service\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ObjectiveType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ObjectiveType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * ObjectiveType constructor.
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
            'row_attr' => ['class' => $isNew ? 'col-12' : 'col-lg-9'],
        ]);

        if (!$isNew) {
            $builder->add('saveBack', Type\SubmitType::class, [
                'label' => $this->translator->trans('Enregistrer et retourner aux opérations', [], 'back'),
                'attr' => [
                    'class' => 'btn-secondary',
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
            'data_class' => Objective::class,
            'translation_domain' => 'form',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'wallet_objective';
    }
}