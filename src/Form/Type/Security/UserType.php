<?php

declare(strict_types=1);

namespace App\Form\Type\Security;

use App\Entity\Security\Group;
use App\Entity\Security\User;
use App\Form\Widget as WidgetType;
use App\Service\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * UserType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UserType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * UserType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator) {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $builder->add('login', Type\TextType::class, [
            'label' => $this->translator->trans("Username", [], 'security_form'),
            'label_html' => true,
            'attr' => [
                'placeholder' => $this->translator->trans("Enter a username", [], 'security_form'),
            ],
            'row_attr' => [
                'class' => 'col-md-4',
            ],
        ]);

        $builder->add('email', Type\EmailType::class, [
            'label' => $this->translator->trans('E-mail', [], 'security_form'),
            'label_html' => true,
            'attr' => [
                'placeholder' => $this->translator->trans('Please enter an email address.', [], 'security_form'),
            ],
            'row_attr' => [
                'class' => 'col-md-4',
            ],
            'constraints' => [new Assert\Email()],
        ]);

        if (!$isNew) {

            $builder->add('lastName', Type\TextType::class, [
                'label' => $this->translator->trans('Last name', [], 'security_form'),
                'label_html' => true,
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Enter a name', [], 'security_form'),
                ],
                'row_attr' => [
                    'class' => 'col-md-4',
                ],
            ]);

            $builder->add('firstName', Type\TextType::class, [
                'label' => $this->translator->trans('First name', [], 'security_form'),
                'label_html' => true,
                'required' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Enter a first name', [], 'security_form'),
                ],
                'row_attr' => [
                    'class' => 'col-md-4',
                ],
            ]);
        }

        $builder->add('group', EntityType::class, [
            'label' => $this->translator->trans('Group', [], 'security_form'),
            'label_html' => true,
            'class' => Group::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('g')
                    ->orderBy('g.adminName', 'ASC');
            },
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
            'placeholder' => $this->translator->trans('Select an option', [], 'security_form'),
            'row_attr' => [
                'class' => 'col-md-4',
            ],
            'constraints' => [new Assert\NotBlank()],
        ]);

        if ($isNew) {

            $builder->add('plainPassword', Type\RepeatedType::class, [
                'label' => false,
                'label_html' => true,
                'type' => Type\PasswordType::class,
                'invalid_message' => $this->translator->trans('The passwords do not match.', [], 'security_form'),
                'first_options' => [
                    'label' => $this->translator->trans('Password', [], 'security_form'),
                    'label_html' => true,
                    'attr' => [
                        'placeholder' => $this->translator->trans('Enter the password', [], 'security_form'),
                    ],
                    'row_attr' => [
                        'class' => 'col-md-6 password-generator',
                    ],
                    'constraints' => [
                        new Assert\NotBlank(message: $this->translator->trans('Please enter a password.', [], 'security_form')),
                    ],
                ],
                'second_options' => [
                    'label' => $this->translator->trans('Confirm password', [], 'security_form'),
                    'label_html' => true,
                    'attr' => [
                        'placeholder' => $this->translator->trans('Enter the password', [], 'security_form'),
                    ],
                    'row_attr' => [
                        'class' => 'col-md-6',
                    ],
                    'constraints' => [
                        new Assert\NotBlank(message: $this->translator->trans('Please confirm your password.', [], 'security_form')),
                    ],
                ],
            ]);

        } else {

            $builder->add('active', Type\CheckboxType::class, [
                'required' => false,
                'label_html' => true,
                'display' => 'button',
                'color' => 'primary',
                'label' => $this->translator->trans('Activate account', [], 'security_form'),
                'attr' => ['class' => 'w-100'],
                'row_attr' => [
                    'class' => 'col-md-4 d-flex align-items-end',
                ],
            ]);
        }

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'website' => null,
            'translation_domain' => 'security_form',
        ]);
    }
}
