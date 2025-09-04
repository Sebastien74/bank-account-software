<?php

declare(strict_types=1);

namespace App\Form\Type\Security\Admin;

use App\Entity\Security\Group;
use App\Form\Widget\SubmitType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * GroupPasswordType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GroupPasswordType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * GroupPasswordType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('plainPassword', Type\RepeatedType::class, [
            'label' => false,
            'mapped' => false,
            'type' => Type\PasswordType::class,
            'invalid_message' => $this->translator->trans('Les mots de passe sont différents', [], 'validators_cms'),
            'constraints' => [new NotBlank()],
            'first_options' => [
                'label' => $this->translator->trans('Mot de passe', [], 'security_cms'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez le mot de passe', [], 'security_cms'),
                    'group' => 'col-md-6 password-generator',
                ],
            ],
            'second_options' => [
                'label' => $this->translator->trans('Confirmation du mot de passe', [], 'security_cms'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez le mot de passe', [], 'security_cms'),
                    'group' => 'col-md-6',
                ],
            ],
        ]);

        $save = new SubmitType($this->coreLocator);
        $save->add($builder, [
            'only_save' => true,
            'as_ajax' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Group::class,
            'website' => null,
        ]);
    }
}
