<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Core\Website;
use App\Entity\Security\User;
use App\Service\Interface\CoreLocatorInterface;
use App\Service\Interface\DataFixturesInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * WebsiteFixtures.
 *
 * WebsiteModel Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class WebsiteFixtures extends BaseFixtures implements DependentFixtureInterface
{
    /**
     * WebsiteFixtures constructor.
     */
    public function __construct(
        protected CoreLocatorInterface $coreLocator,
        private readonly DataFixturesInterface $fixtures,
    ) {
        parent::__construct($coreLocator);
    }

    /**
     * loadData.
     *
     * @throws \Exception
     */
    protected function loadData(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference('webmaster', User::class);

        $website = new Website();

        $this->fixtures->website()->initialize($website, $this->locale, $user);

        $website->setAdminName('Site principal');
        $website->setSlug('default');

        $configuration = $website->getConfiguration();
        $configuration->setAsDefault(true);

        $manager->persist($website);
        $manager->flush();

        $this->addReference('website', $website);
        $this->addReference('configuration', $configuration);
    }

    public function getDependencies(): array
    {
        return [
            SecurityFixtures::class,
        ];
    }
}
