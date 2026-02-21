<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Service\CoreLocatorInterface;
use App\Twig\CoreRuntime;
use Exception;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ExportType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ExportType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * ExportType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly CoreRuntime $coreRuntime,
    ) {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * @throws Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('classname', Type\HiddenType::class, [
            'data' => $options['classname'],
        ]);

        if ($options['dates'] && $options['exportModal']) {

            $builder->add('startDate', Type\DateType::class, [
                'required' => false,
                'label_html' => true,
                'label' => $this->translator->trans('Start date', [], 'core_form'),
                'widget' => 'single_text',
                'format' => $this->coreRuntime->formatDate($this->coreLocator->locale())->datepickerPHP,
                'html5' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Select a date', [], 'core_form'),
                    'class' => 'js-datepicker',
                ],
                'row_attr' => ['class' => 'col-6'],
            ]);

            $builder->add('endDate', Type\DateType::class, [
                'required' => false,
                'label_html' => true,
                'label' => $this->translator->trans('End date', [], 'core_form'),
                'widget' => 'single_text',
                'format' => $this->coreRuntime->formatDate($this->coreLocator->locale())->datepickerPHP,
                'html5' => false,
                'attr' => [
                    'placeholder' => $this->translator->trans('Select a date', [], 'core_form'),
                    'class' => 'js-datepicker',
                ],
                'row_attr' => ['class' => 'col-6'],
            ]);

            $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();
                $start = $data['startDate'] ?? null;
                $end = $data['endDate'] ?? null;
                if ($start && $end && $start > $end) {
                    $form->get('startDate')->addError(
                        new FormError($this->translator->trans('The start date must be earlier than the end date.', [], 'core_form'))
                    );
                }
            });
        }

        if ($options['exportModal']) {
            $builder->add('save', Type\SubmitType::class, [
                'label' => $this->translator->trans('Export', [], 'core_form'),
                'attr' => [
                    'class' => 'btn-primary',
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'classname' => null,
            'floating' => true,
            'dates' => true,
            'exportModal' => true,
            'translation_domain' => 'core_form',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'export';
    }
}
