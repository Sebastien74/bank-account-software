<?php

declare(strict_types=1);

namespace App\Form\Type\Security;

use App\Form\Type\RecaptchaType;
use App\Service\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * LoginType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LoginType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * LoginType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $loginType = 'email' == $options['login_type'] ? Type\EmailType::class : Type\TextType::class;
        $loginInputName = 'email' == $options['login_type'] ? 'email' : 'login';
        $loginLabel = 'email' == $options['login_type']
            ? $this->translator->trans('E-mail', [], 'security_form')
            : $this->translator->trans("Username", [], 'security_form');
        $loginPlaceholder = 'email' == $options['login_type']
            ? $this->translator->trans('Enter your e-mail', [], 'security_form')
            : $this->translator->trans("Enter your username", [], 'security_form');
        $constraints = [new NotBlank()];
        if (Type\EmailType::class === $loginType) {
            $constraints[] = new Email();
        }

        $builder->add($loginInputName, $loginType, [
            'label' => $loginLabel,
            'label_html' => true,
            'row_attr' => ['class' => 'col-lg-12 mb-3'],
            'attr' => [
                'placeholder' => $loginPlaceholder,
                'autocomplete' => 'off',
                'autofocus' => false,
                'class' => 'py-2',
            ],
            'constraints' => $constraints,
        ]);

        $builder->add('_password', Type\PasswordType::class, [
            'label' => $this->translator->trans('Password', [], 'security_form'),
            'label_html' => true,
            'row_attr' => ['class' => 'col-lg-12 mb-3'],
            'attr' => [
                'placeholder' => $this->translator->trans('Enter your password', [], 'security_form'),
                'autocomplete' => 'off',
                'autofocus' => false,
                'class' => 'py-2',
            ],
            'constraints' => [new NotBlank()],
        ]);

        $builder->add('_remember_me', Type\CheckboxType::class, [
            'label' => $this->translator->trans('Remember me', [], 'security_form'),
            'label_html' => true,
            'required' => false,
            'label_attr' => ['class' => 'lh-1 d-inline-block'],
            'data' => true,
        ]);

        $builder->add('secure', RecaptchaType::class);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => $this->translator->trans('Log in', [], 'security_form'),
            'row_attr' => ['class' => 'col-lg-12'],
            'attr' => ['class' => 'btn btn-primary w-100', 'icon' => false],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id' => 'authenticate',
            'data_class' => null,
            'login_type' => $_ENV['SECURITY_ADMIN_LOGIN_TYPE'],
            'translation_domain' => 'security_form',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
