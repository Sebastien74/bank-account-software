<?php

declare(strict_types=1);

namespace App\Form\Widget;

use App\Service\CoreLocatorInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType as SymfonySubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SubmitType
{
    private TranslatorInterface $translator;

    /**
     * SubmitType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * To add submit buttons.
     */
    public function add(FormBuilderInterface $builder): void
    {
        $data = $builder->getData();
        $isNew = !$data || method_exists($data, 'getId') && !$data->getId() || !method_exists($data, 'getId');

        $btnName = $isNew ? 'saveEdit' : 'saveBack';
        $btnLabel = $isNew ? $this->translator->trans('Enregistrer et éditer', [], 'back') : $this->translator->trans('Enregistrer et retourner à la liste', [], 'back');
        $builder->add($btnName, SymfonySubmitType::class, [
            'label' => $btnLabel,
            'attr' => [
                'class' => $isNew ? 'btn-secondary-dark' : 'btn-primary',
            ],
            'row_attr' => ['class' => $isNew ? 'me-2' : 'ms-2'],
        ]);

        $builder->add('save', SymfonySubmitType::class, [
            'label' => $this->translator->trans('Enregistrer', [], 'back'),
            'attr' => [
                'class' => $isNew ? 'btn-primary ms-2' : 'btn-secondary ms-2',
            ],
        ]);
    }
}