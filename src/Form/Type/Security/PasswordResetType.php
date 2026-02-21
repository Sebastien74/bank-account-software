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
            'invalid_message' => $this->translator->trans('The passwords do not match.', [], 'security_form'),
            'first_options' => [
                'label' => $this->translator->trans('Password', [], 'security_form'),
                'label_html' => true,
                'attr' => [
                    'placeholder' => $this->translator->trans('Enter password', [], 'security_form'),
                    'group' => 'col-12 mb-3',
                    'class' => 'pt-2 pb-2 password-checker',
                ],
            ],
            'second_options' => [
                'label' => $this->translator->trans('Confirm password', [], 'security_form'),
                'label_html' => true,
                'attr' => [
                    'placeholder' => $this->translator->trans('Enter password', [], 'security_form'),
                    'group' => 'col-12 mb-3',
                    'class' => 'pt-2 pb-2',
                ],
            ],
        ]);

        $builder->add('secure', RecaptchaType::class);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => $this->translator->trans('Save', [], 'security_form'),
            'row_attr' => ['class' => 'col-lg-12'],
            'attr' => ['class' => 'dash-btn w-100 justify-content-center', 'icon' => 'unlock-alt'],
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
