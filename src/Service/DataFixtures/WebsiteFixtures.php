<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\Website;
use App\Entity\Security\User;
use App\Repository\Core\WebsiteRepository;
use App\Service\Development\EntityService;
use App\Service\Interface\DataFixturesInterface;
use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * WebsiteFixtures.
 *
 * WebsiteModel Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => WebsiteFixtures::class, 'key' => 'website_fixtures'],
])]
class WebsiteFixtures
{
    private const bool GENERATE_TRANSLATIONS = false;
    private array $websites = [];
    private array $yamlConfiguration = [];

    /**
     * WebsiteFixtures constructor.
     */
    public function __construct(
        private readonly DataFixturesInterface $fixtures,
        private readonly EntityService $entityService,
        private readonly WebsiteRepository $websiteRepository,
        private readonly string $projectDir,
    ) {
    }

    /**
     * Get Yaml WebsiteModel configuration.
     *
     * @throws Exception
     */
    private function getYamlConfiguration(?string $yamlConfigDirname = null): void
    {
        $this->websites = $this->websiteRepository->findAll();
        $filesystem = new Filesystem();
        $configDirname = $this->projectDir.'/bin/data/config/';
        $configDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $configDirname);
        $configFileDirname = 0 === count($this->websites) ? $configDirname.'default.yaml' : ($yamlConfigDirname ? $configDirname.$yamlConfigDirname.'.yaml' : null);

        if ($configFileDirname && !is_dir($configFileDirname) && $filesystem->exists($configFileDirname)) {
            $configuration = Yaml::parseFile($configFileDirname);
            $this->yamlConfiguration = is_array($configuration) ? $configuration : $this->yamlConfiguration;
        }
    }

    /**
     * Initialize WebsiteModel.
     *
     * @throws Exception
     */
    public function initialize(
        Website $website,
        string $locale,
        ?User $user = null,
        ?string $yamlConfigDirname = null,
        ?Website $websiteToDuplicate = null,
    ): void {

        $this->getYamlConfiguration($yamlConfigDirname);

        $asMainWebsite = 0 === count($this->websites);

        if ($asMainWebsite) {
            $website->setActive(true);
        }

        $website->setCreatedBy($user);
        $website->setCacheClearDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
        $website->setUploadDirname(uniqid());

        $yamlConfiguration = $this->yamlConfiguration;
        $locale = !empty($yamlConfiguration['locale']) && $asMainWebsite ? $yamlConfiguration['locale'] : $locale;

        $this->fixtures->configuration()->add($website, $yamlConfiguration, $locale, $user);
        $this->fixtures->security()->execute($website);
        $configuration = $website->getConfiguration();
        $this->entityService->website($website);
        $this->entityService->createdBy($user);
        $this->entityService->execute($website, $locale);
        if ($asMainWebsite && self::GENERATE_TRANSLATIONS) {
            $this->fixtures->translations()->generate($configuration, $this->websites);
        }
    }
}
