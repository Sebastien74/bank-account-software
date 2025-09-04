<?php

declare(strict_types=1);

namespace App\Form\Manager\Core;

use App\Entity\Core as CoreEntities;
use App\Service\Interface\CoreLocatorInterface;
use App\Service\Interface\DataFixturesInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Form;

/**
 * WebsiteManager.
 *
 * Manage admin WebsiteModel form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => WebsiteManager::class, 'key' => 'core_website_form_manager'],
])]
class WebsiteManager
{
    /**
     * WebsiteManager constructor.
     */
    public function __construct(
        private readonly DataFixturesInterface $fixtures,
        private readonly CoreLocatorInterface $coreLocator,
    ) {
    }

    /**
     * @prePersist
     *
     * @throws \Exception
     */
    public function prePersist(CoreEntities\Website $website, CoreEntities\Website $currentWebsite, array $interface, Form $form): void
    {
        $this->fixtures->website()->initialize($website, $website->getConfiguration()->getLocale(), null, $form->get('yaml_config')->getData(), $form->get('website_to_duplicate')->getData());
    }

    /**
     * @preUpdate
     *
     * @throws \Exception
     */
    public function preUpdate(CoreEntities\Website $website): void
    {
        $configuration = $website->getConfiguration();
        $locale = $configuration->getLocale();
        $locales = $configuration->getLocales();

        /* Remove default locale if in locales */
        if (in_array($locale, $locales)) {
            unset($locales[array_search($locale, $locales)]);
            $configuration->setLocales($locales);
        }

        $this->setSecurity($website, $configuration);
        $this->cacheDomains($configuration);
    }

    /**
     * To set security.
     *
     * @throws \Exception
     */
    private function setSecurity(CoreEntities\Website $website, CoreEntities\Configuration $configuration): void
    {
        $security = $website->getSecurity();
        $websites = $this->coreLocator->em()->getRepository(CoreEntities\Website::class)->findAll();

        foreach ($websites as $websiteDb) {
            if ($websiteDb->getId() !== $website->getId()) {
                /** @var CoreEntities\Security $securityDb */
                $securityDb = $websiteDb->getSecurity();
                $securityDb->setAdminPasswordDelay($security->getAdminPasswordDelay());
                $this->coreLocator->em()->persist($securityDb);
            }
        }
    }

    /**
     * Set cache Domains.
     */
    private function cacheDomains(CoreEntities\Configuration $configuration): void
    {
        $dirname = $this->coreLocator->cacheDir().'/domains.cache.json';
        $filesystem = new Filesystem();
        if ($filesystem->exists($dirname)) {
            $filesystem->remove($dirname);
            $domains = $this->coreLocator->em()->getRepository(CoreEntities\Domain::class)
                ->createQueryBuilder('d')
                ->andWhere('d.configuration = :configuration')
                ->setParameter('configuration', $configuration)
                ->getQuery()
                ->getResult();
            $cacheData = [];
            foreach ($domains as $domain) {
                $cacheData[$configuration->getId()][$domain->getLocale()][] = [
                    'name' => $domain->getName(),
                    'locale' => $domain->getLocale(),
                    'asDefault' => $domain->isAsDefault(),
                ];
            }
            $fp = fopen($dirname, 'w');
            fwrite($fp, json_encode($cacheData, JSON_PRETTY_PRINT));
            fclose($fp);
        }
    }
}
