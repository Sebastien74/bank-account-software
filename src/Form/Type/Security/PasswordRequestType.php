<?php

declare(strict_types=1);

namespace App\Form\Type\Security;

use App\Form\Type\RecaptchaType;
use App\Service\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * PasswordRequestType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PasswordRequestType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * PasswordRequestType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', Type\EmailType::class, [
            'label' => $this->translator->trans('Your email', [], 'security_form'),
            'label_html' => true,
            'attr' => [
                'placeholder' => $this->translator->trans('Enter your email address', [], 'security_form'),
                'class' => 'pt-2 pb-2',
            ],
            'constraints' => [
                new Assert\NotBlank(),
                new Assert\Email(),
            ],
        ]);

        $builder->add('secure', RecaptchaType::class);

        $builder->add('submit', Type\SubmitType::class, [
            'label' => $this->translator->trans('Send', [], 'security_form'),
            'row_attr' => ['class' => 'col-lg-12'],
            'attr' => ['class' => 'dash-btn w-100 justify-content-center', 'icon' => 'unlock-alt'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'translation_domain' => 'security_form',
        ]);
    }
}
