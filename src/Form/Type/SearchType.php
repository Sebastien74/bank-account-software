<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Service\CoreLocatorInterface;
use Exception;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * SearchType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SearchType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * SearchType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator) {
        $this->translator = $this->coreLocator->translator();
    }

    /**
     * @throws Exception
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('text', Type\TextType::class, [
            'required' => false,
            'label' => false,
            'attr' => ['placeholder' => $this->translator->trans('Search', [], 'back')],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'csrf_protection' => false,
            'classname' => null,
            'floating' => true,
            'translation_domain' => 'core_form',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
