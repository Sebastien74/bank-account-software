<?php

declare(strict_types=1);

namespace App\Form\Type\Wallet;

use App\Entity\Wallet\Category;
use App\Form\Widget\FontawesomeType;
use App\Service\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CategoryType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CategoryType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * CategoryType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isNew = !$builder->getData()->getId();

        $builder->add('adminName', Type\TextType::class, [
            'label' => $this->translator->trans('Intitulé', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un intitulé', [], 'admin'),
            ],
            'constraints' => [new Assert\NotBlank([
                'message' => $this->translator->trans('Veuillez saisir un initulé.', [], 'admin'),
            ])],
            'row_attr' => ['class' => $isNew ? 'col-12' : 'col-lg-9'],
        ]);

        if (!$isNew) {
            $builder->add('icon', FontawesomeType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'select-icons',
                    'group' => 'col-md-2',
                ],
                'row_attr' => ['class' => 'col-lg-3'],
            ]);
        }

        $btnName = $isNew ? 'saveEdit' : 'saveBack';
        $btnLabel = $isNew ? $this->translator->trans('Enregistrer et éditer', [], 'admin') : $this->translator->trans('Enregistrer et retourner à la liste', [], 'admin');
        $builder->add($btnName, Type\SubmitType::class, [
            'label' => $btnLabel,
            'attr' => [
                'class' => $isNew ? 'btn-secondary-dark' : 'btn-dark',
            ],
            'row_attr' => ['class' => $isNew ? 'me-2' : 'ms-2'],
        ]);

        $builder->add('save', Type\SubmitType::class, [
            'label' => $this->translator->trans('Enregistrer', [], 'admin'),
            'attr' => [
                'class' => 'btn-secondary ms-2',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
            'translation_domain' => 'form',
        ]);
    }
}