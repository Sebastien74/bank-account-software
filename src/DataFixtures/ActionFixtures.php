<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Core\Module;
use App\Entity\Layout\Action;
use App\Entity\Security\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * ActionFixtures.
 *
 * Action Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ActionFixtures extends BaseFixtures implements DependentFixtureInterface
{
    private int $position = 1;

    protected function loadData(ObjectManager $manager): void
    {
        foreach ($this->getActions() as $config) {
            $action = $this->addAction($config);
            $this->addReference($action->getSlug(), $action);
        }
    }

    /**
     * Generate Action.
     */
    private function addAction(array $config): Action
    {
        /** @var User $user */
        $user = $this->getReference('webmaster', User::class);

        /** @var Module $module */
        $module = $this->getReference($config[6], Module::class);

        $action = new Action();
        $action->setAdminName($config[0]);
        $action->setController($config[1]);
        $action->setAction($config[2]);
        $action->setEntity($config[3]);
        $action->setSlug($config[4]);
        $action->setIconClass($config[5]);
        $action->setModule($module);
        $action->setDropdown(!empty($config[7]));
        $action->setPosition($this->position);
        $action->setCreatedBy($user);

        ++$this->position;
        $this->manager->persist($action);
        $this->manager->flush();

        return $action;
    }

    /**
     * Get Actions config.
     */
    private function getActions(): array
    {
        return [

        ];
    }

    public function getDependencies(): array
    {
        return [
            ModuleFixtures::class,
            SecurityFixtures::class,
        ];
    }
}
