<?php

declare(strict_types=1);

namespace App\Repository\Wallet;

use App\Entity\Wallet\Operation;
use App\Entity\Wallet\Wallet;
use App\Service\Wallet\Statistics\Period;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * StatisticsRepository.
 *
 * Modèle de lecture des statistiques du compte.
 *
 * Toutes les méthodes retournent des scalaires agrégés en base : aucune entité
 * n'est hydratée. Une page de statistiques parcourt deux ans d'opérations sur
 * une vingtaine d'axes ; les charger en mémoire pour les additionner en PHP
 * serait le premier écueil.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class StatisticsRepository
{
    /**
     * Expression du sens de l'opération.
     *
     * Le type d'opération fait foi ; la sous-catégorie ne sert que de repli
     * lorsqu'aucun type n'est renseigné.
     */
    private const string IS_INCOME = "(ot.type = 'incomes' OR (ot.id IS NULL AND sc.type = 'incomes'))";

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * Revenus, dépenses et nombre d'opérations sur une période.
     *
     * @return array{incomes: float, expenses: float, count: int}
     */
    public function totals(Wallet $wallet, Period $period): array
    {
        if ($period->isEmpty()) {
            return ['incomes' => 0.0, 'expenses' => 0.0, 'count' => 0];
        }

        $row = $this->scoped($wallet, $period)
            ->select(sprintf(
                'COALESCE(SUM(CASE WHEN %1$s THEN o.amount ELSE 0 END), 0) AS incomes,
                 COALESCE(SUM(CASE WHEN %1$s THEN 0 ELSE o.amount END), 0) AS expenses,
                 COUNT(o.id) AS nb',
                self::IS_INCOME
            ))
            ->getQuery()
            ->getSingleResult();

        return [
            'incomes' => round((float) $row['incomes'], 2),
            'expenses' => round((float) $row['expenses'], 2),
            'count' => (int) $row['nb'],
        ];
    }

    /**
     * Totaux mois par mois sur une période, en une seule requête.
     *
     * @return array<string, array{incomes: float, expenses: float, count: int}> indexé par « AAAA-MM »
     */
    public function monthlyTotals(Wallet $wallet, Period $period): array
    {
        if ($period->isEmpty()) {
            return [];
        }

        $rows = $this->scoped($wallet, $period)
            ->select(sprintf(
                'SUBSTRING(o.date, 1, 7) AS period,
                 COALESCE(SUM(CASE WHEN %1$s THEN o.amount ELSE 0 END), 0) AS incomes,
                 COALESCE(SUM(CASE WHEN %1$s THEN 0 ELSE o.amount END), 0) AS expenses,
                 COUNT(o.id) AS nb',
                self::IS_INCOME
            ))
            ->groupBy('period')
            ->orderBy('period', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $totals = [];
        foreach ($rows as $row) {
            $totals[(string) $row['period']] = [
                'incomes' => round((float) $row['incomes'], 2),
                'expenses' => round((float) $row['expenses'], 2),
                'count' => (int) $row['nb'],
            ];
        }

        return $totals;
    }

    /**
     * Dépenses ventilées par catégorie et sous-catégorie.
     *
     * Jointure gauche volontaire : une opération sans sous-catégorie doit
     * apparaître en « Non classé » et non disparaître des totaux.
     *
     * @return array<int, array{categoryId: ?int, categoryName: ?string, subCategoryId: ?int, subCategoryName: ?string, total: float, count: int}>
     */
    public function expensesByCategory(Wallet $wallet, Period $period): array
    {
        if ($period->isEmpty()) {
            return [];
        }

        $rows = $this->scoped($wallet, $period)
            ->leftJoin('sc.category', 'c')
            ->select('c.id AS categoryId, c.adminName AS categoryName, c.icon AS categoryIcon,
                      sc.id AS subCategoryId, sc.adminName AS subCategoryName,
                      COALESCE(SUM(o.amount), 0) AS total, COUNT(o.id) AS nb')
            ->andWhere(sprintf('NOT %s', self::IS_INCOME))
            ->groupBy('c.id')
            ->addGroupBy('sc.id')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'categoryId' => null !== $row['categoryId'] ? (int) $row['categoryId'] : null,
            'categoryName' => $row['categoryName'],
            'categoryIcon' => $row['categoryIcon'] ?? null,
            'subCategoryId' => null !== $row['subCategoryId'] ? (int) $row['subCategoryId'] : null,
            'subCategoryName' => $row['subCategoryName'],
            'total' => round((float) $row['total'], 2),
            'count' => (int) $row['nb'],
        ], $rows);
    }

    /**
     * Dépenses par catégorie et par mois, pour la carte de chaleur.
     *
     * @return array<string, array<int|string, float>> [catégorie][AAAA-MM] => total
     */
    public function expensesByCategoryAndMonth(Wallet $wallet, Period $period): array
    {
        if ($period->isEmpty()) {
            return [];
        }

        $rows = $this->scoped($wallet, $period)
            ->leftJoin('sc.category', 'c')
            ->select('c.adminName AS categoryName, SUBSTRING(o.date, 1, 7) AS period, COALESCE(SUM(o.amount), 0) AS total')
            ->andWhere(sprintf('NOT %s', self::IS_INCOME))
            ->groupBy('c.id')
            ->addGroupBy('period')
            ->getQuery()
            ->getArrayResult();

        $grid = [];
        foreach ($rows as $row) {
            $category = $row['categoryName'] ?? 'Non classé';
            $grid[$category][(string) $row['period']] = round((float) $row['total'], 2);
        }

        return $grid;
    }

    /**
     * Dépenses par bénéficiaire.
     *
     * @return array<int, array{name: string, total: float, count: int}>
     */
    public function expensesByBeneficiary(Wallet $wallet, Period $period, ?int $limit = null): array
    {
        if ($period->isEmpty()) {
            return [];
        }

        $builder = $this->scoped($wallet, $period)
            ->leftJoin('o.outsider', 'os')
            ->select('os.adminName AS name, COALESCE(SUM(o.amount), 0) AS total, COUNT(o.id) AS nb')
            ->andWhere(sprintf('NOT %s', self::IS_INCOME))
            ->groupBy('os.id')
            ->orderBy('total', 'DESC');

        if (null !== $limit) {
            $builder->setMaxResults($limit);
        }

        return array_map(static fn (array $row): array => [
            'name' => (string) ($row['name'] ?? 'Non identifié'),
            'total' => round((float) $row['total'], 2),
            'count' => (int) $row['nb'],
        ], $builder->getQuery()->getArrayResult());
    }

    /**
     * Dépenses par bénéficiaire et par mois, en une requête.
     *
     * Sert à mesurer la régularité d'apparition d'un tiers : itérer les mois côté
     * PHP en interrogeant la base à chaque tour serait douze requêtes pour rien.
     *
     * @return array<string, array<string, float>> [bénéficiaire][AAAA-MM] => total
     */
    public function beneficiaryMonthlyTotals(Wallet $wallet, Period $period): array
    {
        if ($period->isEmpty()) {
            return [];
        }

        $rows = $this->scoped($wallet, $period)
            ->leftJoin('o.outsider', 'os')
            ->select('os.adminName AS name, SUBSTRING(o.date, 1, 7) AS period, COALESCE(SUM(o.amount), 0) AS total')
            ->andWhere(sprintf('NOT %s', self::IS_INCOME))
            ->groupBy('os.id')
            ->addGroupBy('period')
            ->getQuery()
            ->getArrayResult();

        $totals = [];
        foreach ($rows as $row) {
            $totals[(string) ($row['name'] ?? 'Non identifié')][(string) $row['period']] = round((float) $row['total'], 2);
        }

        return $totals;
    }

    /**
     * Plus grosses dépenses unitaires de la période.
     *
     * @return array<int, array{date: \DateTimeInterface, label: string, beneficiary: ?string, subCategory: ?string, amount: float}>
     */
    public function largestExpenses(Wallet $wallet, Period $period, int $limit = 8): array
    {
        if ($period->isEmpty()) {
            return [];
        }

        $rows = $this->scoped($wallet, $period)
            ->leftJoin('o.outsider', 'os')
            ->select('o.date AS date, o.adminName AS label, os.adminName AS beneficiary, sc.adminName AS subCategory, o.amount AS amount')
            ->andWhere(sprintf('NOT %s', self::IS_INCOME))
            ->orderBy('o.amount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'date' => $row['date'],
            'label' => (string) $row['label'],
            'beneficiary' => $row['beneficiary'],
            'subCategory' => $row['subCategory'],
            'amount' => round((float) $row['amount'], 2),
        ], $rows);
    }

    /**
     * Revenus ventilés par sous-catégorie.
     *
     * @return array<int, array{name: ?string, total: float, count: int}>
     */
    public function incomesBySubCategory(Wallet $wallet, Period $period): array
    {
        if ($period->isEmpty()) {
            return [];
        }

        $rows = $this->scoped($wallet, $period)
            ->select('sc.adminName AS name, COALESCE(SUM(o.amount), 0) AS total, COUNT(o.id) AS nb')
            ->andWhere(self::IS_INCOME)
            ->groupBy('sc.id')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'name' => $row['name'] ?? 'Non classé',
            'total' => round((float) $row['total'], 2),
            'count' => (int) $row['nb'],
        ], $rows);
    }

    /**
     * Bénéficiaires d'un poste de dépense sur une période.
     *
     * @return array<int, array{name: string, total: float, count: int}>
     */
    public function beneficiariesForSubCategory(Wallet $wallet, Period $period, int $subCategoryId): array
    {
        if ($period->isEmpty()) {
            return [];
        }

        $rows = $this->scoped($wallet, $period)
            ->leftJoin('o.outsider', 'os')
            ->select('os.adminName AS name, COALESCE(SUM(o.amount), 0) AS total, COUNT(o.id) AS nb')
            ->andWhere('sc.id = :subCategory')
            ->setParameter('subCategory', $subCategoryId)
            ->groupBy('os.id')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'name' => (string) ($row['name'] ?? 'Non identifié'),
            'total' => round((float) $row['total'], 2),
            'count' => (int) $row['nb'],
        ], $rows);
    }

    /**
     * Opérations d'un poste de dépense sur une période.
     *
     * @return array<int, array{id: int, date: \DateTimeInterface, label: string, beneficiary: ?string, amount: float, pointed: bool}>
     */
    public function operationsForSubCategory(Wallet $wallet, Period $period, int $subCategoryId): array
    {
        if ($period->isEmpty()) {
            return [];
        }

        $rows = $this->scoped($wallet, $period)
            ->leftJoin('o.outsider', 'os')
            ->select('o.id AS id, o.date AS date, o.adminName AS label, os.adminName AS beneficiary, o.amount AS amount, o.pointed AS pointed')
            ->andWhere('sc.id = :subCategory')
            ->setParameter('subCategory', $subCategoryId)
            ->orderBy('o.date', 'DESC')
            ->addOrderBy('o.id', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'date' => $row['date'],
            'label' => (string) $row['label'],
            'beneficiary' => $row['beneficiary'],
            'amount' => round((float) $row['amount'], 2),
            'pointed' => (bool) $row['pointed'],
        ], $rows);
    }

    /**
     * Total d'un poste de dépense sur une période.
     *
     * @return array{total: float, count: int}
     */
    public function subCategoryTotals(Wallet $wallet, Period $period, int $subCategoryId): array
    {
        if ($period->isEmpty()) {
            return ['total' => 0.0, 'count' => 0];
        }

        $row = $this->scoped($wallet, $period)
            ->select('COALESCE(SUM(o.amount), 0) AS total, COUNT(o.id) AS nb')
            ->andWhere('sc.id = :subCategory')
            ->setParameter('subCategory', $subCategoryId)
            ->getQuery()
            ->getSingleResult();

        return ['total' => round((float) $row['total'], 2), 'count' => (int) $row['nb']];
    }

    /**
     * Années comportant au moins une opération.
     *
     * @return int[] ordre décroissant
     */
    public function availableYears(Wallet $wallet): array
    {
        $rows = $this->entityManager->createQueryBuilder()
            ->from(Operation::class, 'o')
            ->select('DISTINCT SUBSTRING(o.date, 1, 4) AS year')
            ->andWhere('o.wallet = :wallet')
            ->andWhere('o.date IS NOT NULL')
            ->setParameter('wallet', $wallet)
            ->orderBy('year', 'DESC')
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): int => (int) $row['year'], $rows);
    }

    /**
     * Première et dernière opération du compte.
     *
     * @return array{first: ?\DateTimeInterface, last: ?\DateTimeInterface}
     */
    public function boundaries(Wallet $wallet): array
    {
        $row = $this->entityManager->createQueryBuilder()
            ->from(Operation::class, 'o')
            ->select('MIN(o.date) AS first, MAX(o.date) AS last')
            ->andWhere('o.wallet = :wallet')
            ->andWhere('o.date IS NOT NULL')
            ->setParameter('wallet', $wallet)
            ->getQuery()
            ->getSingleResult();

        return [
            'first' => $row['first'] ? new \DateTimeImmutable((string) $row['first']) : null,
            'last' => $row['last'] ? new \DateTimeImmutable((string) $row['last']) : null,
        ];
    }

    /**
     * Volumétrie des opérations incomplètes, qui fausseraient les ventilations.
     *
     * @return array{withoutSubCategory: int, withoutBeneficiary: int, withoutType: int, unpointed: int}
     */
    public function dataQuality(Wallet $wallet): array
    {
        $row = $this->entityManager->createQueryBuilder()
            ->from(Operation::class, 'o')
            ->select('SUM(CASE WHEN o.subCategory IS NULL THEN 1 ELSE 0 END) AS withoutSubCategory,
                      SUM(CASE WHEN o.outsider IS NULL THEN 1 ELSE 0 END) AS withoutBeneficiary,
                      SUM(CASE WHEN o.operationType IS NULL THEN 1 ELSE 0 END) AS withoutType,
                      SUM(CASE WHEN o.pointed = false THEN 1 ELSE 0 END) AS unpointed')
            ->andWhere('o.wallet = :wallet')
            ->setParameter('wallet', $wallet)
            ->getQuery()
            ->getSingleResult();

        return [
            'withoutSubCategory' => (int) $row['withoutSubCategory'],
            'withoutBeneficiary' => (int) $row['withoutBeneficiary'],
            'withoutType' => (int) $row['withoutType'],
            'unpointed' => (int) $row['unpointed'],
        ];
    }

    /**
     * Base de requête bornée à un compte et à une période.
     */
    private function scoped(Wallet $wallet, Period $period): QueryBuilder
    {
        return $this->entityManager->createQueryBuilder()
            ->from(Operation::class, 'o')
            ->leftJoin('o.subCategory', 'sc')
            ->leftJoin('o.operationType', 'ot')
            ->andWhere('o.wallet = :wallet')
            ->andWhere('o.date >= :start')
            ->andWhere('o.date < :end')
            ->setParameter('wallet', $wallet)
            ->setParameter('start', $period->start)
            ->setParameter('end', $period->end);
    }
}
