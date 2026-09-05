<?php

declare(strict_types=1);

namespace App\Form\Type\Wallet;

use App\Entity\Wallet\SubCategory;
use App\Form\Widget\FontawesomeType;
use App\Form\Widget\SubmitType;
use App\Service\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * SubCategoryType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SubCategoryType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * SubCategoryType constructor.
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
            'label' => $this->translator->trans('Intitulé', [], 'back'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un intitulé', [], 'back'),
            ],
            'constraints' => [
                new Assert\NotBlank(message: $this->translator->trans('Veuillez saisir un initulé.', [], 'back'))
            ],
            'row_attr' => ['class' => $isNew ? 'col-12' : 'col-lg-6'],
        ]);

        if (!$isNew) {

            $builder->add('slug', Type\TextType::class, [
                'label_html' => true,
                'label' => $this->translator->trans('Code', [], 'back'),
                'attr' => [
                    'placeholder' => $this->translator->trans('Saisissez un code', [], 'back'),
                ],
                'constraints' => [
                    new Assert\NotBlank(message: $this->translator->trans('Veuillez saisir un code.', [], 'back'))
                ],
                'row_attr' => ['class' => 'col-lg-3'],
            ]);

            $builder->add('icon', FontawesomeType::class, [
                'required' => false,
                'attr' => ['class' => 'select-icons'],
                'row_attr' => ['class' => 'col-lg-3'],
            ]);
        }

        $save = new SubmitType($this->coreLocator);
        $save->add($builder);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SubCategory::class,
            'translation_domain' => 'form',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'wallet_sub_category';
    }
}
