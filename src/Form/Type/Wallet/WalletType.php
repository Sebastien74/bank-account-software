<?php

declare(strict_types=1);

namespace App\Form\Type\Wallet;

use App\Entity\Wallet\Wallet;
use App\Form\Widget\SubmitType;
use App\Service\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * WalletType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class WalletType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * WalletType constructor.
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
            'label' => $this->translator->trans('Nom du compte', [], 'back'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un nom', [], 'back'),
            ],
            'constraints' => [
                new Assert\NotBlank(['message' => $this->translator->trans('Veuillez saisir un initulé.', [], 'back')])
            ],
            'row_attr' => ['class' => 'col-12'],
        ]);

        $builder->add('initialAmount', Type\NumberType::class, [
            'label_html' => true,
            'label' => $this->translator->trans('Montant initial', [], 'back'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un montant', [], 'back'),
            ],
            'row_attr' => ['class' => $isNew ? 'col-12' : 'col-lg-9'],
            'constraints' => [
                new Assert\NotBlank(['message' => $this->translator->trans('Veuillez saisir un montant.', [], 'back'),]),
            ],
        ]);

        $save = new SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Wallet::class,
            'translation_domain' => 'form',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'wallet_wallet';
    }
}