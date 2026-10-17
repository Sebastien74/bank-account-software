<?php

declare(strict_types=1);

namespace App\Tests\Functional\Back\Wallet;

use App\Entity\Wallet\Operation;
use App\Entity\Wallet\Outsider;
use App\Entity\Wallet\SubCategory;
use App\Entity\Wallet\Wallet;
use App\Tests\Functional\Back\BackOfficeWebTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Ajout d'une opération depuis la modale de `back_operation_index`.
 *
 * Le tiers se donne de deux façons — par la liste, ou par le champ libre
 * « Nouveau tiers » qui n'est pas mappé sur l'entité. Les trois cas sont
 * couverts ici parce que le contrôle qui les arbitre lit la valeur soumise :
 * posé sur l'entité, il refusait toute saisie d'un nouveau tiers.
 */
final class OperationCreateTest extends BackOfficeWebTestCase
{
    #[Test]
    public function a_new_outsider_typed_in_the_free_field_creates_the_operation(): void
    {
        $this->logInAsAdmin();
        $wallet = $this->createWallet('Compte courant', 1000.0);
        $subCategory = $this->createSubCategory('Revenus financiers', 'recipes');

        $this->submitOperation($wallet, ['adminName' => 'Mini Golf'], $subCategory);

        self::assertResponseRedirects();
        $this->em->clear();
        $operation = $this->em->getRepository(Operation::class)->findOneBy([]);
        self::assertInstanceOf(Operation::class, $operation);
        self::assertSame('Mini Golf', $operation->getOutsider()?->getAdminName());
        self::assertSame(100.0, $operation->getAmount());
    }

    #[Test]
    public function an_existing_outsider_picked_in_the_list_creates_the_operation(): void
    {
        $this->logInAsAdmin();
        $wallet = $this->createWallet('Compte courant', 1000.0);
        $subCategory = $this->createSubCategory('Revenus financiers', 'recipes');
        $outsider = $this->createOutsider('Mini Golf');

        $this->submitOperation($wallet, ['outsider' => (string) $outsider->getId()], $subCategory);

        self::assertResponseRedirects();
        $this->em->clear();
        $operation = $this->em->getRepository(Operation::class)->findOneBy([]);
        self::assertInstanceOf(Operation::class, $operation);
        self::assertSame($outsider->getId(), $operation->getOutsider()?->getId());
        self::assertCount(1, $this->em->getRepository(Outsider::class)->findAll());
    }

    #[Test]
    public function an_operation_without_any_outsider_is_refused(): void
    {
        $this->logInAsAdmin();
        $wallet = $this->createWallet('Compte courant', 1000.0);
        $subCategory = $this->createSubCategory('Revenus financiers', 'recipes');

        $crawler = $this->submitOperation($wallet, [], $subCategory);

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            'Sélectionnez un tiers existant ou saisissez un nouveau tiers.',
            $crawler->filter('form[name="wallet_operation"]')->text()
        );
        $this->em->clear();
        self::assertSame(0, $this->em->getRepository(Operation::class)->count([]));
    }

    /**
     * Poste la modale d'ajout avec un tiers décrit par $outsiderFields.
     */
    private function submitOperation(Wallet $wallet, array $outsiderFields, SubCategory $subCategory): \Symfony\Component\DomCrawler\Crawler
    {
        $url = $this->router->generate('back_operation_index', ['wallet' => $wallet->getId()]);
        $crawler = $this->client->request('GET', $url);
        self::assertResponseIsSuccessful();

        $form = $crawler->filter('form[name="wallet_operation"]')->form();
        $values = $form->getPhpValues();
        $values['wallet_operation'] = array_merge($values['wallet_operation'], [
            'outsider' => '',
            'adminName' => '',
            'date' => '11/10/2026',
            'amount' => '100',
            'subCategory' => (string) $subCategory->getId(),
            'save' => '',
        ], $outsiderFields);

        return $this->client->request('POST', $form->getUri(), $values);
    }
}
