<?php

declare(strict_types=1);

namespace App\Tests\Functional\Back\Wallet;

use App\Entity\Wallet\Wallet;
use App\Tests\Functional\Back\BackOfficeWebTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Non-régression visuelle de la liste des opérations (`back_operation_index`).
 *
 * Les sélecteurs visent des identifiants stables et des rôles ARIA : aucune
 * classe de composant Bootstrap n'est nommée ici.
 */
final class OperationIndexTest extends BackOfficeWebTestCase
{
    private const int THEAD_COLUMNS = 7;

    #[Test]
    public function operation_index_responds_successfully(): void
    {
        $this->logInAsAdmin();
        $wallet = $this->seedWalletWithCurrentMonthOperations();

        $this->client->request('GET', $this->operationIndexUrl($wallet));

        self::assertResponseIsSuccessful();
    }

    #[Test]
    public function operation_index_renders_a_table_with_seven_columns(): void
    {
        $this->logInAsAdmin();
        $wallet = $this->seedWalletWithCurrentMonthOperations();

        $crawler = $this->client->request('GET', $this->operationIndexUrl($wallet));

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('#operations-index table'));
        self::assertCount(1, $crawler->filter('#operations-index table thead'));
        self::assertCount(
            self::THEAD_COLUMNS,
            $crawler->filter('#operations-index table thead th'),
            'L\'en-tête du tableau des opérations compte sept colonnes.'
        );
    }

    #[Test]
    public function operation_index_renders_one_row_per_operation(): void
    {
        $this->logInAsAdmin();
        $wallet = $this->seedWalletWithCurrentMonthOperations();

        $crawler = $this->client->request('GET', $this->operationIndexUrl($wallet));

        self::assertResponseIsSuccessful();
        self::assertCount(3, $crawler->filter('#operations-index table tbody tr'));
    }

    #[Test]
    public function operation_index_renders_the_period_navigation(): void
    {
        $this->logInAsAdmin();
        $wallet = $this->seedWalletWithCurrentMonthOperations();

        $crawler = $this->client->request('GET', $this->operationIndexUrl($wallet));

        self::assertResponseIsSuccessful();

        $nav = $crawler->filter('#operations-index .period-nav');
        self::assertCount(1, $nav, 'La barre de période est rendue une fois.');
        self::assertCount(
            2,
            $nav->filter('.period-step'),
            'La barre de période porte le pas « mois précédent » et le pas « mois suivant ».'
        );
        self::assertCount(1, $nav->filter('.period-label'));
        self::assertCount(
            2,
            $nav->filter('.period-year a'),
            'La barre de période porte l\'année précédente et l\'année suivante.'
        );
    }

    #[Test]
    public function operation_index_on_an_empty_month_renders_the_empty_state(): void
    {
        $this->logInAsAdmin();
        $wallet = $this->seedWalletWithCurrentMonthOperations();

        $crawler = $this->client->request('GET', $this->operationIndexUrl($wallet, 2001, 1));

        self::assertResponseIsSuccessful();
        self::assertCount(0, $crawler->filter('#operations-index table tbody tr'));
        self::assertCount(1, $crawler->filter('#operations-index .empty-state'));
        self::assertCount(1, $crawler->filter('#operations-index .empty-state__title'));
    }

    #[Test]
    public function operation_index_on_an_empty_month_keeps_the_period_navigation(): void
    {
        $this->logInAsAdmin();
        $wallet = $this->seedWalletWithCurrentMonthOperations();

        $crawler = $this->client->request('GET', $this->operationIndexUrl($wallet, 2001, 1));

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('#operations-index .period-nav'));
    }

    #[Test]
    public function operation_index_marks_the_last_breadcrumb_link_as_current(): void
    {
        $this->logInAsAdmin();
        $wallet = $this->seedWalletWithCurrentMonthOperations();

        $crawler = $this->client->request('GET', $this->operationIndexUrl($wallet));

        self::assertResponseIsSuccessful();

        $items = $crawler->filter('nav[aria-label] ol li');
        self::assertGreaterThan(1, $items->count(), 'Le fil d\'Ariane des opérations compte plusieurs maillons.');

        $current = $crawler->filter('nav[aria-label] ol li[aria-current="page"]');
        self::assertCount(1, $current, 'Un seul maillon doit porter aria-current="page".');
        self::assertSame(
            $items->last()->text(),
            $current->text(),
            'Le maillon courant doit être le dernier du fil d\'Ariane.'
        );
        self::assertCount(
            0,
            $current->filter('a'),
            'Le maillon courant n\'est pas un lien.'
        );
    }

    #[Test]
    public function operation_index_pagination_counter_has_no_leftover_placeholder(): void
    {
        $this->logInAsAdmin();
        $wallet = $this->seedWalletWithCurrentMonthOperations();

        $crawler = $this->client->request('GET', $this->operationIndexUrl($wallet));

        self::assertResponseIsSuccessful();

        $counter = $crawler->filter('#operations-index .pagination-counter');
        self::assertCount(1, $counter);

        $text = preg_replace('/\s+/u', ' ', trim($counter->text()));
        self::assertStringNotContainsString('{', (string) $text, 'Le compte ne doit porter aucun marqueur résiduel.');
        self::assertStringNotContainsString('}', (string) $text, 'Le compte ne doit porter aucun marqueur résiduel.');
        self::assertMatchesRegularExpression('/^Affichage de 1 à 3 sur 3$/u', (string) $text);
    }

    private function operationIndexUrl(Wallet $wallet, ?int $year = null, ?int $month = null): string
    {
        $parameters = ['wallet' => $wallet->getId()];
        if (null !== $year && null !== $month) {
            $parameters['year'] = $year;
            $parameters['month'] = str_pad((string) $month, 2, '0', STR_PAD_LEFT);
        }

        return $this->router->generate('back_operation_index', $parameters);
    }

    private function seedWalletWithCurrentMonthOperations(): Wallet
    {
        $wallet = $this->createWallet('Compte courant', 1000.0);
        $subCategory = $this->createSubCategory('Courses');
        $outsider = $this->createOutsider('Supermarché');

        // Operation::$date est mappé en `datetime` : Doctrine refuse un
        // DateTimeImmutable sur cette colonne.
        $month = new \DateTime('first day of this month 12:00:00', new \DateTimeZone('Europe/Paris'));
        foreach ([1, 2, 3] as $offset) {
            $this->createOperation(
                $wallet,
                (clone $month)->modify(sprintf('+%d day', $offset)),
                10.0 * $offset,
                $subCategory,
                $outsider
            );
        }

        return $wallet;
    }
}
