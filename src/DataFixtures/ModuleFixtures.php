<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Core\Module;
use App\Entity\Security\User;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * ModuleFixtures.
 *
 * Module Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ModuleFixtures extends BaseFixtures implements DependentFixtureInterface
{
    private int $position = 1;

    protected function loadData(ObjectManager $manager): void
    {
        foreach ($this->getModules() as $config) {
            $module = $this->generateModule($config);
            $this->addReference($module->getSlug(), $module);
            $this->manager->persist($module);
        }
        $this->manager->flush();
    }

    /**
     * Generate BlockType.
     */
    private function generateModule(array $config): Module
    {
        /** @var User $user */
        $user = $this->getReference('webmaster', User::class);

        $module = new Module();
        $module->setAdminName($config[0]);
        $module->setSlug($config[1]);
        $module->setRole($config[2]);
        $module->setIconClass($config[3]);
        $module->setPosition($this->position);
        $module->setCreatedBy($user);

        ++$this->position;

        $this->manager->persist($module);

        return $module;
    }

    /**
     * Get Modules config.
     */
    private function getModules(): array
    {
        return [
            [$this->translator->trans('Pages', [], 'admin'), 'pages', 'ROLE_PAGE', 'fal network-wired'],
            [$this->translator->trans('Traductions', [], 'admin'), 'translation', 'ROLE_TRANSLATION', 'fal globe-stand'],
            [$this->translator->trans('Utilisateurs', [], 'admin'), 'user', 'ROLE_USERS', 'fal users'],
            [$this->translator->trans('Actions personnalisées', [], 'admin'), 'customs-actions', 'ROLE_CUSTOMS_ACTIONS', 'fal flame'],
            [$this->translator->trans('Pages sécurisées (Users front)', [], 'admin'), 'secure-page', 'ROLE_SECURE_PAGE', 'fal shield'],
            [$this->translator->trans('Modules sécurisés', [], 'admin'), 'secure-module', 'ROLE_SECURE_MODULE', 'fal shield'],
            [$this->translator->trans('Classes personnalisées', [], 'admin'), 'css', 'ROLE_INTERNAL', 'fal paint-brush'],
            [$this->translator->trans('Édition générale', [], 'admin'), 'edit', 'ROLE_EDIT', 'fal pen-nib'],
        ];
    }

    public function getDependencies(): array
    {
        return [
            SecurityFixtures::class,
        ];
    }
}
