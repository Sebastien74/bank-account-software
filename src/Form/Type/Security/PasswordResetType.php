<?php

declare(strict_types=1);

namespace App\Form\Type\Security;

use App\Form\Model\Security\Admin\PasswordResetModel;
use App\Form\Type\RecaptchaType;
use App\Service\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * PasswordResetType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PasswordResetType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * PasswordResetType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plainPassword', Type\RepeatedType::class, [
            'label' => false,
            'type' => Type\PasswordType::class,
            'invalid_message' => $this->translator->trans('Les mots de passe ne correspondent pas.', [], 'security_form'),
            'first_options' => [
                'label' => $this->translator->trans('Mot de passe', [], 'security_form'),
                'label_html' => true,
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un mot de passe', [], 'security_form'),
                    'group' => 'col-12 mb-3',
                    'class' => 'password-checker',
                ],
            ],
            'second_options' => [
                'label' => $this->translator->trans('Confirmez le mot de passe', [], 'security_form'),
                'label_html' => true,
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un mot de passe', [], 'security_form'),
                    'group' => 'col-12 mb-3',
                ],
            ],
        ]);

        $builder->add('secure', RecaptchaType::class);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => $this->translator->trans('Enregistrer', [], 'security_form'),
            'row_attr' => ['class' => 'col-lg-12'],
            'attr' => ['class' => 'btn btn-primary w-100', 'icon' => false],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PasswordResetModel::class,
            'translation_domain' => 'security_form',
        ]);
    }
}
