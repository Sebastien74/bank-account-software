<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\Security;
use App\Entity\Core\Website;
use App\Entity\Security\User;
use App\Service\Interface\CoreLocatorInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * SecurityFixtures.
 *
 * Security ConfigurationModel Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => SecurityFixtures::class, 'key' => 'security_fixtures'],
])]
class SecurityFixtures
{
    /**
     * SecurityFixtures constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly Environment $templating,
    )
    {
    }

    /**
     * Execute.
     *
     * @throws \Exception
     */
    public function execute(Website $website): void
    {
        $this->addWebsiteToMaster($website);
        $this->addConfiguration($website);
        $this->addWebsite($website);
    }

    /**
     * Add ConfigurationModel.
     */
    private function addWebsiteToMaster(Website $website): void
    {
        /** @var User $webmaster */
        $webmaster = $this->coreLocator->em()->getRepository(User::class)->findOneBy(['login' => 'webmaster']);
        if ($webmaster) {
            $webmaster->addWebsite($website);
            $this->coreLocator->em()->persist($webmaster);
        }
    }

    /**
     * Add ConfigurationModel.
     *
     * @throws \Exception
     */
    private function addConfiguration(Website $website): void
    {
        $security = new Security();
        $security->setWebsite($website);
        $security->setSecurityKey($this->coreLocator->alphanumericKey(30));
        $this->coreLocator->em()->persist($security);

        $website->setSecurity($security);
        $this->coreLocator->em()->persist($website);
    }

    /**
     * Add WebsiteModel to customer.
     */
    private function addWebsite(Website $website): void
    {
        /** @var User $customer */
        $customer = $this->coreLocator->em()->getRepository(User::class)->findOneBy(['login' => 'customer']);
        if ($customer) {
            $customer->addWebsite($website);
            $this->coreLocator->em()->persist($customer);
        }
    }
}
