<?php

declare(strict_types=1);

namespace App\Form\Type\Seo\Configuration;

use App\Entity\Core\Website;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * WebsiteType.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class WebsiteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('api', ApiType::class, [
            'label' => false,
        ]);

        $builder->add('information', InformationType::class, [
            'label' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Website::class,
            'website' => null,
            'translation_domain' => 'admin',
        ]);
    }
}
