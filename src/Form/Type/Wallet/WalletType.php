<?php

declare(strict_types=1);

namespace App\Form\Type\Wallet;

use App\Entity\Wallet\Wallet;
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
            'label' => $this->translator->trans('Nom du compte', [], 'back'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un nom', [], 'back'),
            ],
            'constraints' => [new Assert\NotBlank([
                'message' => $this->translator->trans('Veuillez saisir un nom pour votre compte.', [], 'back'),
            ])],
        ]);

        $btnName = $isNew ? 'saveEdit' : 'saveBack';
        $btnLabel = $isNew ? $this->translator->trans('Enregistrer et éditer', [], 'back') : $this->translator->trans('Enregistrer et retourner à la liste', [], 'back');
        $builder->add($btnName, Type\SubmitType::class, [
            'label' => $btnLabel,
            'attr' => [
                'class' => $isNew ? 'btn-secondary-dark' : 'btn-dark',
            ],
            'row_attr' => ['class' => $isNew ? 'me-2' : 'ms-2'],
        ]);

        $builder->add('save', Type\SubmitType::class, [
            'label' => $this->translator->trans('Enregistrer', [], 'back'),
            'attr' => [
                'class' => 'btn-secondary ms-2',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Wallet::class,
            'translation_domain' => 'form',
        ]);
    }
}