<?php

declare(strict_types=1);

namespace App\Tests\Functional\Back\Wallet;

use App\Tests\Functional\Back\BackOfficeWebTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Non-régression visuelle de la liste des comptes (`back_wallet_index`).
 *
 * Les sélecteurs visent des identifiants stables et des rôles ARIA : aucune
 * classe de composant Bootstrap n'est nommée ici.
 */
final class WalletIndexTest extends BackOfficeWebTestCase
{
    #[Test]
    public function wallet_index_responds_successfully(): void
    {
        $this->logInAsAdmin();
        $this->createWallet('Compte courant', 1000.0);

        $this->client->request('GET', $this->router->generate('back_wallet_index'));

        self::assertResponseIsSuccessful();
    }

    #[Test]
    public function wallet_index_renders_one_card_per_wallet(): void
    {
        $this->logInAsAdmin();
        $this->createWallet('Compte courant', 1000.0, 1);
        $this->createWallet('Livret A', 5000.0, 2);
        $this->createWallet('Compte joint', 250.0, 3);

        $crawler = $this->client->request('GET', $this->router->generate('back_wallet_index'));

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('#wallets-index'));
        self::assertCount(3, $crawler->filter('#wallets-index .card'));
    }

    #[Test]
    public function wallet_index_renders_the_add_action(): void
    {
        $this->logInAsAdmin();
        $this->createWallet('Compte courant');

        $crawler = $this->client->request('GET', $this->router->generate('back_wallet_index'));

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('[data-bs-target="#add-entity-modal"]'));
        self::assertCount(1, $crawler->filter('#add-entity-modal'));
    }

    #[Test]
    public function wallet_index_without_wallet_renders_the_empty_state(): void
    {
        $this->logInAsAdmin();

        $crawler = $this->client->request('GET', $this->router->generate('back_wallet_index'));

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('#wallets-index'));
        self::assertCount(1, $crawler->filter('.empty-state'));
    }

    #[Test]
    public function wallet_index_marks_the_last_breadcrumb_link_as_current(): void
    {
        $this->logInAsAdmin();
        $this->createWallet('Compte courant');

        $crawler = $this->client->request('GET', $this->router->generate('back_wallet_index'));

        self::assertResponseIsSuccessful();

        $items = $crawler->filter('nav[aria-label] ol li');
        self::assertGreaterThan(0, $items->count());

        $current = $crawler->filter('nav[aria-label] ol li[aria-current="page"]');
        self::assertCount(1, $current, 'Un seul maillon doit porter aria-current="page".');
        self::assertSame(
            $items->last()->text(),
            $current->text(),
            'Le maillon courant doit être le dernier du fil d\'Ariane.'
        );
    }

    #[Test]
    public function wallet_index_pagination_counter_has_no_leftover_placeholder(): void
    {
        $this->logInAsAdmin();
        $this->createWallet('Compte courant', 1000.0, 1);
        $this->createWallet('Livret A', 5000.0, 2);

        $crawler = $this->client->request('GET', $this->router->generate('back_wallet_index'));

        self::assertResponseIsSuccessful();

        $counter = $crawler->filter('.pagination-counter');
        self::assertCount(1, $counter);

        $text = preg_replace('/\s+/u', ' ', trim($counter->text()));
        self::assertStringNotContainsString('{', (string) $text, 'Le compte ne doit porter aucun marqueur résiduel.');
        self::assertStringNotContainsString('}', (string) $text, 'Le compte ne doit porter aucun marqueur résiduel.');
        self::assertMatchesRegularExpression('/^Affichage de 1 à 2 sur 2$/u', (string) $text);
    }
}
