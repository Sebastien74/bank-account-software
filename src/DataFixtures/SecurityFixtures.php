<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Security\Group;
use App\Entity\Security\Role;
use App\Entity\Security\User;
use App\Service\Urlizer;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Symfony\Component\Yaml\Yaml;

/**
 * SecurityFixtures.
 *
 * Security Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SecurityFixtures extends BaseFixtures
{
    private int $position = 1;
    private ?User $createdBy = null;

    /**
     * loadData.
     *
     * @throws Exception
     */
    protected function loadData(ObjectManager $manager): void
    {
        $this->manager = $manager;
        $this->addRoles();
        foreach ($this->getUsers() as $userConfig) {
            $this->addUser($userConfig);
        }
        $this->manager->flush();
    }

    /**
     * Add Roles.
     */
    private function addRoles(): void
    {
        $yamlRoles = $this->getYamlRoles();
        $position = 1;
        foreach ($yamlRoles as $roleName => $config) {
            $adminName = !empty($config['fr']) ? $config['fr'] : $roleName;
            $role = new Role();
            $role->setAdminName($adminName);
            $role->setName($roleName);
            $role->setSlug(Urlizer::urlize($roleName));
            $role->setPosition($position);
            $this->addReference($roleName, $role);
            $this->manager->persist($role);
            ++$position;
        }
    }

    /**
     * Get Yaml Roles.
     */
    private function getYamlRoles(bool $onlyName = false): array
    {
        $securityDirname = $this->projectDir.'/bin/data/fixtures/security.yaml';
        $securityDirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $securityDirname);
        $yamlRoles = Yaml::parseFile($securityDirname);

        if ($onlyName) {
            $roles = [];
            foreach ($yamlRoles as $roleName => $config) {
                $roles[] = $roleName;
            }
            return $roles;
        }

        return $yamlRoles;
    }

    /**
     * Get Users configuration.
     */
    private function getUsers(): array
    {
        $users[] = [
            'markup' => '232',
            'email' => 'fournier.sebastien@outlook.com',
            'login' => 'webmaster',
            'roles' => $this->getYamlRoles(true),
            'lastname' => 'Bank Account Software',
            'group' => 'Interne',
            'password' => '$2y$10$yzsckDg/ad8P/MiLzuOPCehisJDkLKfO45LB4u9KtUd.T.LDjFVTq',
            'code' => 'internal',
            'active' => true,
            'picture' => 'webmaster.svg',
        ];

        return $users;
    }

    /**
     * Add User.
     *
     * @throws Exception
     */
    private function addUser(array $userConfig): User
    {
        $userConfig = (object) $userConfig;

        $user = new User();
        $user->setEmail($userConfig->email);
        $user->setLogin($userConfig->login);
        $user->setLastname($userConfig->lastname);
        $user->setPassword($userConfig->password);
        $user->setActive($userConfig->active);
        $user->setLocale($this->locale);
        $user->setActive(true);
        $user->agreeTerms();

        if (property_exists($userConfig, 'firstname')) {
            $user->setFirstName($userConfig->firstname);
        }

        if (property_exists($userConfig, 'theme')) {
            $user->setTheme($userConfig->theme);
        }

        if ('webmaster' === $user->getLogin()) {
            $this->createdBy = $user;
        }

        $this->addReference($userConfig->login, $user);
        $this->addGroup((array) $userConfig, $user);

        $this->manager->persist($user);

        return $user;
    }

    /**
     * Add Group.
     */
    private function addGroup(array $userConfig, User $user): void
    {
        $userConfig = (object) $userConfig;
        $group = new Group();
        $group->setAdminName($userConfig->group);
        $group->setSlug($userConfig->code);
        $group->setCreatedBy($this->createdBy);
        $group->setPosition($this->position);
        foreach ($userConfig->roles as $role) {
            /** @var Role $roleReference */
            $roleReference = $this->getReference($role, Role::class);
            $group->addRole($roleReference);
        }
        $user->setGroup($group);
        $this->manager->persist($group);
        ++$this->position;
    }
}
