<?php

declare(strict_types=1);

namespace App\Tests\Functional\Back;

use App\Entity\Security\Group;
use App\Entity\Security\Role;
use App\Entity\Security\User;
use App\Entity\Wallet\Category;
use App\Entity\Wallet\Operation;
use App\Entity\Wallet\OperationType;
use App\Entity\Wallet\Outsider;
use App\Entity\Wallet\SubCategory;
use App\Entity\Wallet\Wallet;
use Doctrine\DBAL\Exception as DbalException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Socle des tests fonctionnels du back-office.
 *
 * Fournit une base vierge, un utilisateur administrateur et un client
 * authentifié. La base de test est `<DB_NAME>_test`, créée par
 * `php bin/console --env=test doctrine:database:create` puis
 * `doctrine:schema:create`.
 */
abstract class BackOfficeWebTestCase extends WebTestCase
{
    protected const string ADMIN_LOGIN = 'test';
    protected const string ADMIN_PASSWORD = 'Test1234!';
    protected const string ADMIN_GROUP_SLUG = 'tests-administrateurs';

    protected KernelBrowser $client;
    protected EntityManagerInterface $em;
    protected RouterInterface $router;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();
        $this->client->disableReboot();

        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->router = $container->get(RouterInterface::class);

        $this->purgeDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->client, $this->em, $this->router);
    }

    /**
     * Vide toutes les tables du schéma de test.
     *
     * @throws DbalException
     */
    protected function purgeDatabase(): void
    {
        $connection = $this->em->getConnection();
        $platform = $connection->getDatabasePlatform();
        $tables = $connection->createSchemaManager()->listTableNames();

        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $table) {
            $connection->executeStatement($platform->getTruncateTableSQL($table, true));
        }
        $connection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        $this->em->clear();
    }

    /**
     * Crée l'administrateur de test et ouvre sa session.
     */
    protected function logInAsAdmin(): User
    {
        $user = $this->createAdminUser();
        $this->client->loginUser($user, 'user');

        return $user;
    }

    protected function createAdminUser(): User
    {
        $role = new Role();
        $role->setName('ROLE_ADMIN');
        $role->setAdminName('Administrateur');
        $role->setSlug('role-admin');
        $this->em->persist($role);

        $group = new Group();
        $group->setAdminName('Tests administrateurs');
        $group->setSlug(self::ADMIN_GROUP_SLUG);
        $group->addRole($role);
        $this->em->persist($group);

        $user = new User();
        $user->setLogin(self::ADMIN_LOGIN);
        $user->setEmail('test@bank-account-software.local');
        $user->setFirstName('Compte');
        $user->setLastName('Test');
        $user->setLocale('fr');
        $user->setGroup($group);
        $user->setPassword(
            static::getContainer()->get(UserPasswordHasherInterface::class)
                ->hashPassword($user, self::ADMIN_PASSWORD)
        );
        $this->em->persist($user);

        $this->em->flush();

        // Le cache de rôles de BaseSecurity::getRoles() est écrit sur disque et
        // survit aux exécutions : on le retire pour que le groupe fraîchement
        // créé soit relu en base.
        $this->clearRolesCache();

        return $user;
    }

    protected function clearRolesCache(): void
    {
        $cache = \dirname(__DIR__, 3).'/var/cache/security/roles-'.self::ADMIN_GROUP_SLUG.'.cache';
        if (is_file($cache)) {
            @unlink($cache);
        }
    }

    protected function createWallet(string $name, float $initialAmount = 0.0, int $position = 1): Wallet
    {
        $wallet = new Wallet();
        $wallet->setAdminName($name);
        $wallet->setInitialAmount($initialAmount);
        $wallet->setPosition($position);
        $this->em->persist($wallet);
        $this->em->flush();

        return $wallet;
    }

    protected function createSubCategory(string $name, string $type = 'expenses'): SubCategory
    {
        // Category::prePersist() lit le type depuis son OperationType : la
        // chaîne complète est donc obligatoire, même pour une sous-catégorie
        // de test.
        $operationType = new OperationType();
        $operationType->setAdminName('expenses' === $type ? 'Dépenses' : 'Recettes');
        $operationType->setType($type);
        $this->em->persist($operationType);

        $category = new Category();
        $category->setAdminName($name.' (catégorie)');
        $category->setOperationtype($operationType);
        $this->em->persist($category);

        $subCategory = new SubCategory();
        $subCategory->setAdminName($name);
        $subCategory->setType($type);
        $subCategory->setCategory($category);
        $this->em->persist($subCategory);

        $this->em->flush();

        return $subCategory;
    }

    protected function createOutsider(string $name): Outsider
    {
        $outsider = new Outsider();
        $outsider->setAdminName($name);
        $this->em->persist($outsider);
        $this->em->flush();

        return $outsider;
    }

    protected function createOperation(
        Wallet $wallet,
        \DateTimeInterface $date,
        float $amount,
        ?SubCategory $subCategory = null,
        ?Outsider $outsider = null,
    ): Operation {
        $operation = new Operation();
        $operation->setWallet($wallet);
        $operation->setDate($date);
        $operation->setAmount($amount);
        $operation->setSubCategory($subCategory);
        $operation->setOutsider($outsider);
        $operation->setAdminName('Opération du '.$date->format('d/m/Y'));
        $this->em->persist($operation);
        $this->em->flush();

        return $operation;
    }
}
