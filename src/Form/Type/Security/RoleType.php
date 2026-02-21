<?php

declare(strict_types=1);

namespace App\Form\Type\Security;

use App\Entity\Security\Role;
use App\Form\Widget as WidgetType;
use App\Service\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * RoleType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RoleType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * RoleType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('adminName', TextType::class, [
            'label' => $this->translator->trans('Group name', [], 'security_form'),
            'label_html' => true,
            'attr' => [
                'placeholder' => $this->translator->trans('Enter a name', [], 'security_form'),
            ],
            'row_attr' => ['class' => 'col-md-6'],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('name', Type\TextType::class, [
            'label' => $this->translator->trans('Code', [], 'security_form'),
            'label_html' => true,
            'attr' => [
                'placeholder' => $this->translator->trans('Enter a code', [], 'security_form'),
            ],
            'row_attr' => ['class' => 'col-md-6'],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Role::class,
            'translation_domain' => 'security_form',
        ]);
    }
}
