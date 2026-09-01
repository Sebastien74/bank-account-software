<?php

declare(strict_types=1);

namespace App\Service\Wallet\Statistics;

use App\Entity\Wallet\SubCategory;
use App\Entity\Wallet\Wallet;
use App\Repository\Wallet\StatisticsRepository;

/**
 * SubCategoryDetailBuilder.
 *
 * Détail d'un poste de dépense : ce qui a été dépensé, chez qui, et comment
 * cela évolue. C'est la vue vers laquelle pointe chaque poste du tableau de bord.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class SubCategoryDetailBuilder
{
    private const int SERIES_MONTHS = 12;

    /** Mois affichés après celui sélectionné, pour pouvoir naviguer vers l'avant. */
    private const int SERIES_LOOKAHEAD = 3;

    public function __construct(
        private StatisticsRepository $statisticsRepository,
        private PeriodResolver $periodResolver,
    ) {
    }

    /**
     * @param string $scope « month » ou « year »
     */
    public function build(Wallet $wallet, SubCategory $subCategory, int $year, int $month, string $scope = 'month'): array
    {
        $windows = 'year' === $scope
            ? $this->periodResolver->yearOverYear($year)
            : $this->periodResolver->monthOverMonth($year, $month);

        $id = (int) $subCategory->getId();
        $current = $this->statisticsRepository->subCategoryTotals($wallet, $windows['current'], $id);
        $previous = $this->statisticsRepository->subCategoryTotals($wallet, $windows['previous'], $id);
        $reference = $this->statisticsRepository->totals($wallet, $windows['previous']);

        $beneficiaries = $this->statisticsRepository->beneficiariesForSubCategory($wallet, $windows['current'], $id);
        $max = $beneficiaries[0]['total'] ?? 0.0;
        $coverage = $this->statisticsRepository->boundaries($wallet);

        return [
            'wallet' => $wallet,
            'subCategory' => $subCategory,
            'category' => $subCategory->getCategory(),
            'scope' => $scope,
            'selectedYear' => $year,
            'selectedMonth' => $month,
            'months' => PeriodResolver::months(),
            'years' => $this->statisticsRepository->availableYears($wallet),
            'periodLabel' => $windows['current']->label,
            'comparisonLabel' => $windows['previous']->label,
            'total' => $current['total'],
            'count' => $current['count'],
            'average' => $current['count'] > 0 ? round($current['total'] / $current['count'], 2) : 0.0,
            'previousTotal' => $previous['total'],
            'trend' => Comparison::between($current['total'], $previous['total'], $reference['count'] > 0),
            'beneficiaries' => array_map(static fn (array $row): array => $row + [
                'ratio' => $max > 0 ? round($row['total'] / $max * 100, 1) : 0.0,
                'average' => $row['count'] > 0 ? round($row['total'] / $row['count'], 2) : 0.0,
            ], $beneficiaries),
            'operations' => $this->statisticsRepository->operationsForSubCategory($wallet, $windows['current'], $id),
            'series' => $this->series($wallet, $id, $year, $month, $coverage),
            'navigation' => $this->navigation($year, $month, $coverage),
        ];
    }

    /**
     * Mois précédent et suivant, bornés à l'historique du compte.
     *
     * Sans ces deux repères, la seule navigation possible serait la série, dont
     * le mois sélectionné est toujours le dernier point : on ne pourrait
     * qu'aller vers le passé, jamais revenir.
     *
     * @param array{first: ?\DateTimeInterface, last: ?\DateTimeInterface} $coverage
     *
     * @return array{previous: ?array{year: int, month: int}, next: ?array{year: int, month: int}}
     */
    private function navigation(int $year, int $month, array $coverage): array
    {
        $selected = (new \DateTimeImmutable('now', $this->periodResolver->timezone()))
            ->setDate($year, $month, 1)
            ->setTime(0, 0);

        $first = $coverage['first'] ? \DateTimeImmutable::createFromInterface($coverage['first'])->modify('first day of this month')->setTime(0, 0) : null;
        $last = $coverage['last'] ? \DateTimeImmutable::createFromInterface($coverage['last'])->modify('first day of this month')->setTime(0, 0) : null;

        $previous = $selected->modify('-1 month');
        $next = $selected->modify('+1 month');

        return [
            'previous' => (null === $first || $previous >= $first)
                ? ['year' => (int) $previous->format('Y'), 'month' => (int) $previous->format('n')]
                : null,
            'next' => (null === $last || $next <= $last)
                ? ['year' => (int) $next->format('Y'), 'month' => (int) $next->format('n')]
                : null,
        ];
    }

    /**
     * Montants du poste sur douze mois glissants.
     */
    private function series(Wallet $wallet, int $subCategoryId, int $year, int $month, array $coverage): array
    {
        $points = [];
        $max = 0.0;

        // La fenêtre glisse pour laisser voir les mois suivants quand ils existent :
        // sinon le mois sélectionné est toujours le dernier point et la navigation
        // ne peut que reculer.
        $anchor = (new \DateTimeImmutable('now', $this->periodResolver->timezone()))
            ->setDate($year, $month, 1)
            ->setTime(0, 0)
            ->modify(sprintf('+%d months', self::SERIES_LOOKAHEAD));

        if ($coverage['last'] instanceof \DateTimeInterface) {
            $last = \DateTimeImmutable::createFromInterface($coverage['last'])->modify('first day of this month')->setTime(0, 0);
            $anchor = min($anchor, $last);
        }

        $anchorYear = (int) $anchor->format('Y');
        $anchorMonth = (int) $anchor->format('n');
        $first = $coverage['first'] instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($coverage['first'])->modify('first day of this month')->setTime(0, 0)
            : null;

        foreach ($this->periodResolver->trailingMonths($anchorYear, $anchorMonth, self::SERIES_MONTHS) as $period) {
            $totals = $this->statisticsRepository->subCategoryTotals($wallet, $period, $subCategoryId);
            $max = max($max, $totals['total']);
            $points[] = [
                'label' => sprintf(
                    '%s %s',
                    PeriodResolver::shortMonthName((int) $period->start->format('n')),
                    $period->start->format('y')
                ),
                'year' => (int) $period->start->format('Y'),
                'month' => (int) $period->start->format('n'),
                'total' => $totals['total'],
                'count' => $totals['count'],
                'current' => $period->start->format('Y-m') === sprintf('%04d-%02d', $year, $month),
                // Un mois antérieur à l'historique ne mène nulle part : il reste
                // affiché pour la lecture, mais n'est pas cliquable.
                'available' => null === $first || $period->start >= $first,
            ];
        }

        return array_map(static fn (array $point): array => $point + [
            'ratio' => $max > 0 ? round($point['total'] / $max * 100, 1) : 0.0,
        ], $points);
    }
}
