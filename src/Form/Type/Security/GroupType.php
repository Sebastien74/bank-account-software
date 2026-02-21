<?php

declare(strict_types=1);

namespace App\Form\Type\Security;

use App\Entity\Security\Group;
use App\Entity\Security\Role;
use App\Form\Widget as WidgetType;
use App\Service\CoreLocatorInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * GroupType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GroupType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * GroupType constructor.
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
            'row_attr' => ['class' => 'col-12'],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('roles', EntityType::class, [
            'label' => $this->translator->trans('Roles', [], 'security_form'),
            'label_html' => true,
            'class' => Role::class,
            'query_builder' => function (EntityRepository $er) {
                if (!$this->coreLocator->granted('ROLE_DEV')) {
                    return $er->createQueryBuilder('r')->groupBy('r.id')
                        ->having('SUM(CASE WHEN r.name IN (:roles) THEN 1 ELSE 0 END) = 0')
                        ->setParameter('roles', ['ROLE_DEV']);
                }
                return $er->createQueryBuilder('m')
                    ->orderBy('m.adminName', 'ASC');
            },
            'choice_label' => function ($entity) {
                return strip_tags($entity->getAdminName());
            },
            'multiple' => true,
            'attr' => ['placeholder' => $this->translator->trans('Select an option', [], 'security_form')],
            'row_attr' => ['class' => 'col-12'],
            'constraints' => [new Assert\Count(min: 1, minMessage: $this->translator->trans('You must select at least one group.', [], 'security_form'))],
        ]);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Group::class,
            'website' => null,
            'translation_domain' => 'security_form',
        ]);
    }
}
