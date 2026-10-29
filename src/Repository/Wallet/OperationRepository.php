<?php

declare(strict_types=1);

namespace App\Repository\Wallet;

use App\Entity\Wallet\Operation;
use App\Entity\Wallet\Wallet;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Exception;

/**
 * OperationRepository.
 *
 * @extends ServiceEntityRepository<Operation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class OperationRepository extends ServiceEntityRepository
{
    /** Categories fourre-tout de l'import : une operation qui y tombe n'est pas classee. */
    private const GENERIC_SUB_CATEGORIES = [
        'expenses-other',
        'incomes-other-income-to-be-categorized',
    ];

    /**
     * OperationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Operation::class);
    }

    /**
     * sumBalance.
     *
     * @throws NonUniqueResultException|NoResultException|Exception
     */
    public function sumBalance(Wallet $wallet, bool $pointed = false, ?\DateTimeInterface $limitDate = null): ?float
    {
        $initial = $wallet->getInitialAmount() ?? 0;

        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.subCategory', 'sc')
            ->leftJoin('o.operationType', 'ot')
            ->andWhere('o.wallet = :wallet')
            ->setParameter('wallet', $wallet)
            ->setParameter('initial', $initial)
            ->select("
                (
                    COALESCE(SUM(
                        CASE WHEN ot.type = 'incomes' OR (ot.id IS NULL AND sc.type = 'incomes') THEN o.amount
                             ELSE (0 - o.amount)
                        END
                    ), 0)
                    + :initial
                ) AS balance
            ");

        if ($pointed) {
            $qb->andWhere('o.pointed = :pointed')
                ->setParameter('pointed', true);
        }

        if ($limitDate instanceof DateTimeInterface) {
            $qb->andWhere('o.date < :end')
                ->setParameter('end', $limitDate);
        }

        $balance = $qb->getQuery()->getSingleScalarResult();

        return floatval($balance);
    }

    /**
     * Somme algébrique des opérations pointées d'un compte, hors solde initial.
     *
     * Sert à recaler le solde initial sur le solde bancaire : les saisies
     * manuelles en attente n'y figurent pas encore et ne doivent pas être
     * absorbées par le solde initial.
     *
     * @throws NonUniqueResultException|NoResultException
     */
    public function sumOperations(Wallet $wallet): float
    {
        $total = $this->createQueryBuilder('o')
            ->leftJoin('o.subCategory', 'sc')
            ->leftJoin('o.operationType', 'ot')
            ->andWhere('o.wallet = :wallet')
            ->andWhere('o.pointed = :pointed')
            ->setParameter('wallet', $wallet)
            ->setParameter('pointed', true)
            ->select("
                COALESCE(SUM(
                    CASE WHEN ot.type = 'incomes' OR (ot.id IS NULL AND sc.type = 'incomes') THEN o.amount
                         ELSE (0 - o.amount)
                    END
                ), 0) AS total
            ")
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $total;
    }

    /**
     * Totaux d'un compte pour le mois en cours, calculés en base.
     *
     * Évite de charger la collection d'opérations pour un simple affichage de solde.
     *
     * @return array{expenses: float, incomes: float}
     */
    public function currentMonthTotals(Wallet $wallet, ?\DateTimeZone $tz = null): array
    {
        $tz ??= new \DateTimeZone('Europe/Paris');
        $start = new \DateTimeImmutable('first day of this month 00:00:00', $tz);
        $end = $start->modify('first day of next month');

        $rows = $this->createQueryBuilder('o')
            ->leftJoin('o.subCategory', 'sc')
            ->leftJoin('o.operationType', 'ot')
            ->select("
                COALESCE(SUM(CASE WHEN ot.type = 'incomes' OR (ot.id IS NULL AND sc.type = 'incomes') THEN o.amount ELSE 0 END), 0) AS incomes,
                COALESCE(SUM(CASE WHEN ot.type = 'incomes' OR (ot.id IS NULL AND sc.type = 'incomes') THEN 0 ELSE o.amount END), 0) AS expenses
            ")
            ->andWhere('o.wallet = :wallet')
            ->andWhere('o.date >= :start')
            ->andWhere('o.date < :end')
            ->setParameter('wallet', $wallet)
            ->setParameter('start', $start, \Doctrine\DBAL\Types\Types::DATETIME_IMMUTABLE)
            ->setParameter('end', $end, \Doctrine\DBAL\Types\Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->getSingleResult();

        return [
            'expenses' => (float) $rows['expenses'],
            'incomes' => (float) $rows['incomes'],
        ];
    }

    /**
     * Depense du mois, sous-poste par sous-poste.
     *
     * Le tableau de bord confronte ces totaux aux enveloppes : il lui faut
     * aussi les postes depenses sans enveloppe, sinon le plan paraitrait tenu
     * alors que l'argent est sorti ailleurs.
     *
     * @return array<int, array{name: string, category: string, total: float}> indexe par sous-categorie
     */
    public function monthlyTotalsBySubCategory(Wallet $wallet, int $year, int $month, string $type = 'expenses'): array
    {
        $tz = new \DateTimeZone('Europe/Paris');
        $start = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month), $tz);
        $end = $start->modify('first day of next month');

        $rows = $this->createQueryBuilder('o')
            ->select('sc.id AS subCategoryId, sc.adminName AS name, c.adminName AS categoryName, SUM(o.amount) AS total')
            ->join('o.subCategory', 'sc')
            ->join('sc.category', 'c')
            ->join('o.operationType', 'ot')
            ->andWhere('o.wallet = :wallet')
            ->andWhere('ot.type = :type')
            ->andWhere('o.date >= :start')
            ->andWhere('o.date < :end')
            ->setParameter('wallet', $wallet)
            ->setParameter('type', $type)
            ->setParameter('start', $start, \Doctrine\DBAL\Types\Types::DATETIME_IMMUTABLE)
            ->setParameter('end', $end, \Doctrine\DBAL\Types\Types::DATETIME_IMMUTABLE)
            ->groupBy('sc.id')
            ->getQuery()
            ->getArrayResult();

        $totals = [];
        foreach ($rows as $row) {
            $totals[(int) $row['subCategoryId']] = [
                'name' => (string) $row['name'],
                'category' => (string) $row['categoryName'],
                'total' => (float) $row['total'],
            ];
        }

        return $totals;
    }

    /**
     * Categorie habituelle de chaque tiers, deduite de l'historique.
     *
     * A la saisie, le tiers suffit presque toujours a dire la categorie : la
     * proposer evite de la rechercher dans une liste de plusieurs dizaines
     * d'entrees. Les categories fourre-tout sont ecartees, suggerer
     * « Autres depenses » reviendrait a ne rien suggerer.
     *
     * @return array<int, int> identifiant de tiers => identifiant de categorie
     */
    public function usualSubCategoryByOutsider(): array
    {
        $rows = $this->createQueryBuilder('o')
            ->select('IDENTITY(o.outsider) AS outsider, IDENTITY(o.subCategory) AS subCategory, COUNT(o.id) AS total')
            ->join('o.subCategory', 'sc')
            ->andWhere('o.outsider IS NOT NULL')
            ->andWhere('sc.slug NOT IN (:generic)')
            ->setParameter('generic', self::GENERIC_SUB_CATEGORIES)
            ->groupBy('o.outsider')
            ->addGroupBy('o.subCategory')
            ->orderBy('total', 'DESC')
            ->getQuery()
            ->getArrayResult();

        $usual = [];
        foreach ($rows as $row) {
            // Les lignes arrivent de la plus frequente a la moins frequente :
            // la premiere vue pour un tiers est la bonne.
            $outsider = (int) $row['outsider'];
            if (!isset($usual[$outsider])) {
                $usual[$outsider] = (int) $row['subCategory'];
            }
        }

        return $usual;
    }

    /**
     * Total encaisse par le compte depuis le 1er janvier.
     *
     * C'est le chiffre qui a un sens sur un compte d'epargne : il ne depense
     * rien, donc ni moyenne de depenses ni reste a vivre ; ce qu'on veut
     * savoir, c'est ce qu'on y a mis cette annee.
     */
    public function yearToDateIncomes(Wallet $wallet, ?\DateTimeZone $tz = null): float
    {
        $tz ??= new \DateTimeZone('Europe/Paris');
        $start = new \DateTimeImmutable('first day of January this year 00:00:00', $tz);
        $end = $start->modify('+1 year');

        $total = $this->createQueryBuilder('o')
            ->leftJoin('o.subCategory', 'sc')
            ->leftJoin('o.operationType', 'ot')
            ->select("COALESCE(SUM(CASE WHEN ot.type = 'incomes' OR (ot.id IS NULL AND sc.type = 'incomes') THEN o.amount ELSE 0 END), 0)")
            ->andWhere('o.wallet = :wallet')
            ->andWhere('o.date >= :start')
            ->andWhere('o.date < :end')
            ->setParameter('wallet', $wallet)
            ->setParameter('start', $start, \Doctrine\DBAL\Types\Types::DATETIME_IMMUTABLE)
            ->setParameter('end', $end, \Doctrine\DBAL\Types\Types::DATETIME_IMMUTABLE)
            ->getQuery()
            ->getSingleScalarResult();

        return (float) $total;
    }

    /**
     * sumPerMonthNet.=
     *
     * @throws Exception
     */
    public function sumPerMonth(
        Wallet $wallet,
        \DateTimeInterface $from,
        \DateTimeInterface $to,
        ?\DateTimeZone $tz = null
    ): array
    {
        $results = [];
        $tz ??= new \DateTimeZone('UTC');
        $cursor = (new \DateTimeImmutable($from->format('Y-m-01 00:00:00'), $tz));
        $limit  = (new \DateTimeImmutable($to->format('Y-m-01 00:00:00'), $tz));

        while ($cursor < $limit) {
            $next = $cursor->modify('first day of next month')->setTime(0, 0, 0);
            $qb = $this->createQueryBuilder('o')
                ->leftJoin('o.subCategory', 'sc')
                ->leftJoin('o.operationType', 'ot')
                ->andWhere('o.wallet = :wallet')->setParameter('wallet', $wallet)
                ->andWhere('o.date IS NOT NULL')
                ->andWhere('o.date >= :start')->setParameter('start', $cursor, \Doctrine\DBAL\Types\Types::DATETIME_IMMUTABLE)
                ->andWhere('o.date < :end')->setParameter('end', $next, \Doctrine\DBAL\Types\Types::DATETIME_IMMUTABLE)
                ->andWhere('ot.type = :type OR (ot.id IS NULL AND sc.type = :type)')->setParameter('type', 'expenses')
                ->select('COALESCE(SUM(o.amount), 0)');
            $total = (string) $qb->getQuery()->getSingleScalarResult();
            $results[] = [
                'period' => $cursor->format('Y-m'),
                'total'  => floatval($total),
            ];
            $cursor = $next;
        }

        return $results;
    }

    /**
     * Find Operation by month and year.
     *
     * @throws Exception
     */
    /**
     * Operations tombees dans une categorie fourre-tout, ou sans categorie.
     *
     * L'import range dans « Virements et cheques » ou « Revenus non ventiles »
     * tout libelle qu'il ne sait pas rapprocher d'une enseigne connue. Ces
     * operations faussent toutes les ventilations tant qu'elles n'ont pas ete
     * reclassees a la main : il faut pouvoir les retrouver.
     *
     * @return Operation[]
     */
    public function findUnlinked(?Wallet $wallet = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.subCategory', 'sb')
            ->leftJoin('o.outsider', 'os')
            ->addSelect('sb')
            ->addSelect('os')
            ->andWhere('sb.id IS NULL OR sb.slug IN (:generic)')
            ->setParameter('generic', self::GENERIC_SUB_CATEGORIES)
            ->orderBy('o.date', 'DESC')
            ->addOrderBy('o.id', 'DESC');

        if ($wallet instanceof Wallet) {
            $qb->andWhere('o.wallet = :wallet')->setParameter('wallet', $wallet);
        }

        return $qb->getQuery()->getResult();
    }

    public function countUnlinked(?Wallet $wallet = null): int
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->leftJoin('o.subCategory', 'sb')
            ->andWhere('sb.id IS NULL OR sb.slug IN (:generic)')
            ->setParameter('generic', self::GENERIC_SUB_CATEGORIES);

        if ($wallet instanceof Wallet) {
            $qb->andWhere('o.wallet = :wallet')->setParameter('wallet', $wallet);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    public function findByYearMonth(
        string $year,
        string $month,
        string $sort = 'date',
        string $order = 'ASC',
        ?\DateTimeZone $tz = null,
        ?Wallet $wallet = null): array
    {
        if (!preg_match('/^(0[1-9]|1[0-2])$/', $month)) {
            throw new \InvalidArgumentException('Invalid month format, expected "01".."12".');
        }
        if (!preg_match('/^\d{4}$/', $year)) {
            throw new \InvalidArgumentException('Invalid year format, expected "YYYY".');
        }

        $tz ??= new \DateTimeZone('UTC');
        $start = (new \DateTimeImmutable("$year-$month-01 00:00:00", $tz));
        $nextStart = $start->modify('first day of next month')->setTime(0, 0, 0);

        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.subCategory', 'sb')
            ->leftJoin('o.outsider', 'os')
            ->andWhere('o.date IS NOT NULL')
            ->andWhere('o.date >= :start')
            ->andWhere('o.date < :end')
            ->setParameter('start', $start, \Doctrine\DBAL\Types\Types::DATETIME_IMMUTABLE)
            ->setParameter('end', $nextStart, \Doctrine\DBAL\Types\Types::DATETIME_IMMUTABLE)
            ->addSelect('sb')
            ->addSelect('os');

        if ($wallet instanceof Wallet) {
            $qb->andWhere('o.wallet = :wallet')->setParameter('wallet', $wallet);
        }

        $sort = 'dt' === $sort ? 'date' : ('pt' === $sort ? 'pointed': ('ct' === $sort ? 'category' : ('os' === $sort ? 'outsider' : ('at' === $sort ? 'amount' : $sort))));
        // `$order` est concaténé tel quel dans la clause ORDER BY : seules deux
        // valeurs sont admises, tout le reste retombe sur l'ordre décroissant.
        // `$sort`, lui, est comparé par === à une liste blanche juste au-dessus.
        $order = 'ASC' === strtoupper((string) $order) ? 'ASC' : 'DESC';

        if ('date' === $sort) {
            /*
             * À date égale, l'affichage suit l'ordre dans lequel le solde est
             * calculé : encaissement d'abord, décaissements ensuite (voir
             * WalletModel::currentOperations). Sans ce départage, la colonne
             * « Solde » sautait d'une ligne à l'autre à l'intérieur d'un même
             * jour, puisque les deux ordres divergeaient.
             *
             * Le tri est inversé avec la date : en lecture décroissante, le
             * crédit du jour se lit en bas du groupe, comme sur un relevé.
             */
            $qb->addSelect('CASE WHEN ot.type = :incomeType THEN 0 ELSE 1 END AS HIDDEN incomeFirst')
                ->leftJoin('o.operationType', 'ot')
                ->setParameter('incomeType', 'incomes')
                ->orderBy('o.date', $order)
                ->addOrderBy('incomeFirst', 'ASC' === $order ? 'ASC' : 'DESC')
                ->addOrderBy('o.id', $order);
        } elseif ('amount' === $sort or 'pointed' === $sort) {
            $qb->orderBy('o.'.$sort, $order);
            $qb->addOrderBy('o.id', $order);
        } elseif ('category' === $sort) {
            $qb->orderBy('sb.adminName', $order);
            $qb->addOrderBy('o.id', $order);
        } elseif ('outsider' === $sort) {
            $qb->orderBy('os.adminName', $order);
            $qb->addOrderBy('o.id', $order);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Get statistics for a wallet.
     *
     * @return array
     */
    public function getStats(Wallet $wallet, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('c.id as categoryId, c.adminName as categoryName, sc.id as subCategoryId, sc.adminName as subCategoryName, SUM(o.amount) as total')
            ->join('o.subCategory', 'sc')
            ->join('sc.category', 'c')
            ->leftJoin('o.operationType', 'ot')
            ->andWhere('o.wallet = :wallet')
            ->andWhere('o.date >= :start')
            ->andWhere('o.date <= :end')
            ->andWhere("ot.type = 'expenses' OR (ot.id IS NULL AND sc.type = 'expenses')")
            ->groupBy('c.id', 'sc.id')
            ->setParameter('wallet', $wallet)
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        return $qb->getQuery()->getResult();
    }

    /**
     * Check if a period has any operations.
     */
    public function hasOperations(Wallet $wallet, \DateTimeInterface $start, \DateTimeInterface $end): bool
    {
        $qb = $this->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->andWhere('o.wallet = :wallet')
            ->andWhere('o.date >= :start')
            ->andWhere('o.date <= :end')
            ->setParameter('wallet', $wallet)
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    /**
     * Get available years for a wallet.
     */
    public function getAvailableYears(Wallet $wallet): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('DISTINCT(o.date) as date')
            ->andWhere('o.wallet = :wallet')
            ->andWhere('o.date IS NOT NULL')
            ->setParameter('wallet', $wallet)
            ->orderBy('o.date', 'DESC');

        $results = $qb->getQuery()->getResult();
        $years = [];

        foreach ($results as $result) {
            $date = $result['date'];
            if ($date instanceof \DateTimeInterface) {
                $year = $date->format('Y');
            } elseif (is_string($date)) {
                $year = (new \DateTime($date))->format('Y');
            } else {
                continue;
            }

            if (!in_array($year, $years)) {
                $years[] = $year;
            }
        }

        return $years;
    }

    /**
     * Get the first operation date for a wallet in a specific year.
     */
    public function getFirstOperationDateInYear(Wallet $wallet, int $year): ?\DateTimeInterface
    {
        $start = \DateTime::createFromFormat('Y-m-d H:i:s', "$year-01-01 00:00:00");
        $end = \DateTime::createFromFormat('Y-m-d H:i:s', "$year-12-31 23:59:59");

        $qb = $this->createQueryBuilder('o')
            ->select('MIN(o.date)')
            ->andWhere('o.wallet = :wallet')
            ->andWhere('o.date >= :start')
            ->andWhere('o.date <= :end')
            ->setParameter('wallet', $wallet)
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        $result = $qb->getQuery()->getSingleScalarResult();

        if ($result === null) {
            return null;
        }

        if (is_string($result)) {
            return new \DateTime($result);
        }

        return $result;
    }
}
