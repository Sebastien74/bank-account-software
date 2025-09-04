<?php

declare(strict_types=1);

namespace App\Service\Interface;

use App\Service\DataFixtures as Fixtures;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;

class DataFixturesLocator implements DataFixturesInterface
{
    /**
     * FrontFormManagerLocator constructor.
     */
    public function __construct(
        #[AutowireLocator(Fixtures\ConfigurationFixtures::class, indexAttribute: 'key')] protected ServiceLocator $configurationLocator,
        #[AutowireLocator(Fixtures\SecurityFixtures::class, indexAttribute: 'key')] protected ServiceLocator $securityLocator,
        #[AutowireLocator(Fixtures\TranslationsFixtures::class, indexAttribute: 'key')] protected ServiceLocator $translationLocator,
        #[AutowireLocator(Fixtures\WebsiteFixtures::class, indexAttribute: 'key')] protected ServiceLocator $websiteFileLocator,
    ) {
    }

    /**
     * To get ConfigurationFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function configuration(): Fixtures\ConfigurationFixtures
    {
        return $this->configurationLocator->get('config_fixtures');
    }

    /**
     * To get SecurityFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function security(): Fixtures\SecurityFixtures
    {
        return $this->securityLocator->get('security_fixtures');
    }

    /**
     * To get TranslationsFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function translations(): Fixtures\TranslationsFixtures
    {
        return $this->translationLocator->get('translations_fixtures');
    }

    /**
     * To get WebsiteFixtures.
     *
     * @throws ContainerExceptionInterface
     */
    public function website(): Fixtures\WebsiteFixtures
    {
        return $this->websiteFileLocator->get('website_fixtures');
    }
}
