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

        /*
         * Gradation de la charte (voir .claude/charte-back/composants.md) :
         * l'action principale est pleine, l'action secondaire utile prend la
         * teinte atténuée. Le bouton plein est le dernier de la rangée, donc
         * « Enregistrer » ; l'enregistrement avec retour reste au niveau en
         * dessous. C'était l'inverse, ce qui mettait en avant le détour.
         */
        $btnName = $isNew ? 'saveEdit' : 'saveBack';
        $btnLabel = $isNew ? $this->translator->trans('Enregistrer et éditer', [], 'back') : $this->translator->trans('Enregistrer et retourner à la liste', [], 'back');
        $builder->add($btnName, SymfonySubmitType::class, [
            'label' => $btnLabel,
            'attr' => [
                'class' => 'btn-label-primary',
            ],
            'row_attr' => ['class' => $isNew ? 'me-2' : 'ms-2'],
        ]);

        $builder->add('save', SymfonySubmitType::class, [
            'label' => $this->translator->trans('Enregistrer', [], 'back'),
            'attr' => [
                'class' => 'btn-primary ms-2',
            ],
        ]);
    }
}