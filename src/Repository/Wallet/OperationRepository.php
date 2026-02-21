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
    public function findByYearMonth(
        string $year,
        string $month,
        string $sort = 'date',
        string $order = 'ASC',
        ?\DateTimeZone $tz = null): array
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

        $sort = 'dt' === $sort ? 'date' : ('pt' === $sort ? 'pointed': ('ct' === $sort ? 'category' : ('os' === $sort ? 'outsider' : ('at' === $sort ? 'amount' : $sort))));
        $order = strtoupper($order);

        if ('date' === $sort or 'amount' === $sort or 'pointed' === $sort) {
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
}
