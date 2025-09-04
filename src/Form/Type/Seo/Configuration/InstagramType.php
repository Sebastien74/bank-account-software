<?php

declare(strict_types=1);

namespace App\Form\Type\Seo\Configuration;

use App\Entity\Api\Instagram;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * InstagramType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class InstagramType extends AbstractType
{
    private TranslatorInterface $translator;

    /**
     * InstagramType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->translator = $this->coreLocator->translator();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('accessToken', Type\TextType::class, [
            'required' => false,
            'label' => $this->translator->trans('API token', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez le token', [], 'admin'),
                'group' => 'col-md-8',
            ],
        ]);

        $builder->add('nbrItems', Type\IntegerType::class, [
            'label' => $this->translator->trans('Nombre de posts', [], 'admin'),
            'attr' => [
                'placeholder' => $this->translator->trans('Saisissez un chiffre', [], 'admin'),
                'group' => 'col-md-4',
            ],
        ]);

        $builder->add('intls', CollectionType::class, [
            'label' => false,
            'entry_type' => InstagramIntlType::class,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Instagram::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
