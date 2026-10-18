<?php

declare(strict_types=1);

namespace App\Tests\Functional\Back\Wallet;

use App\Tests\Functional\Back\BackOfficeWebTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Moteur de recherche de la liste des bénéficiaires (`back_outsider_index`).
 */
final class OutsiderSearchTest extends BackOfficeWebTestCase
{
    #[Test]
    public function search_filters_by_name_ignoring_accents_and_case(): void
    {
        $this->logInAsAdmin();
        $this->createOutsider('Café des Sports');
        $this->createOutsider("Cafe'Inn");
        $this->createOutsider('Bouygues Telecom');

        // L'ordre suit la collation de la base, qui range « é » avec « e ».
        self::assertSame(['Café des Sports', "Cafe'Inn"], $this->search('CAFE'));
        self::assertSame(['Café des Sports'], $this->search('café des'));
    }

    #[Test]
    public function symbols_only_or_wildcards_do_not_match_everything(): void
    {
        $this->logInAsAdmin();
        $this->createOutsider('Bouygues Telecom');
        $this->createOutsider('ENGIE');

        self::assertSame([], $this->search('&'));
        self::assertSame([], $this->search('%'));
    }

    #[Test]
    public function an_empty_search_lists_everything_and_shows_the_field(): void
    {
        $this->logInAsAdmin();
        $this->createOutsider('Bouygues Telecom');
        $this->createOutsider('ENGIE');

        $crawler = $this->client->request('GET', $this->router->generate('back_outsider_index'));

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('form[role="search"] input[name="q"]'));
        self::assertCount(2, $crawler->filter('#entities-index tbody tr'));
    }

    #[Test]
    public function no_result_says_so_and_offers_to_show_everything(): void
    {
        $this->logInAsAdmin();
        $this->createOutsider('ENGIE');

        $crawler = $this->client->request('GET', $this->router->generate('back_outsider_index', ['q' => 'introuvable']));

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('introuvable', $crawler->filter('.empty-state')->text());
        self::assertSame('introuvable', $crawler->filter('input[name="q"]')->attr('value'));
    }

    /**
     * Noms des bénéficiaires listés pour une recherche.
     *
     * @return string[]
     */
    private function search(string $query): array
    {
        $crawler = $this->client->request('GET', $this->router->generate('back_outsider_index', ['q' => $query]));
        self::assertResponseIsSuccessful();

        return $crawler->filter('#entities-index tbody .cell-title')->each(static fn ($node): string => $node->text());
    }
}
