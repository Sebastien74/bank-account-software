<?php

declare(strict_types=1);

namespace App\Form\Type\Layout\Block;

use App\Entity\Layout\Block;
use App\Form\Widget as WidgetType;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CardType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CardType extends AbstractType
{
    private const bool ACTIVE_LARGE = false;
    private TranslatorInterface $translator;

    /**
     * CardType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('template', WidgetType\TemplateBlockType::class);

        if (self::ACTIVE_LARGE) {
            $builder->add('controls', CheckboxType::class, [
                'required' => false,
                'display' => 'button',
                'color' => 'outline-info-darken',
                'label' => $this->translator->trans('Mini-fiche large', [], 'admin'),
                'attr' => ['group' => 'col-md-3', 'class' => 'w-100'],
            ]);
        }

        $builder->add('backgroundColorType', WidgetType\BackgroundColorSelectType::class, [
            'label' => $this->translator->trans('Couleur de fond', [], 'admin'),
            'attr' => [
                'class' => 'select-icons',
                'group' => 'col-md-3',
            ],
        ]);

        $intls = new WidgetType\IntlsCollectionType($this->coreLocator);
        $intls->add($builder, [
            'website' => $options['website'],
            'fields' => ['title' => 'col-md-5', 'subTitle' => 'col-md-5', 'body', 'targetLink' => 'col-md-3 add-title', 'targetPage' => 'col-md-3', 'targetLabel' => 'col-md-3', 'targetStyle' => 'col-md-3', 'newTab' => 'col-md-3'],
            'title_force' => true,
        ]);

        $mediaRelations = new WidgetType\MediaRelationsCollectionType($this->coreLocator);
        $mediaRelations->add($builder, ['entry_options' => [
            'onlyMedia' => true,
            'sizes' => true,
            'pictogram' => true,
        ]]);

        $save = new WidgetType\SubmitType($this->coreLocator);
        $save->add($builder, ['btn_back' => true]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Block::class,
            'translation_domain' => 'admin',
            'website' => null,
        ]);
    }
}
