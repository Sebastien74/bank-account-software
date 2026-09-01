<?php

declare(strict_types=1);

namespace App\Service\Wallet\Statistics;

use App\Entity\Wallet\Wallet;
use App\Repository\Wallet\OperationRepository;
use App\Repository\Wallet\StatisticsRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

/**
 * StatisticsBuilder.
 *
 * Assemble l'ensemble des indicateurs du tableau de bord d'un compte.
 *
 * Le contrôleur ne fait que valider l'année et le mois demandés : tout le calcul
 * vit ici, et rien n'est recalculé dans le template.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class StatisticsBuilder
{
    /** Nombre de mois affichés sur les séries glissantes. */
    private const int SERIES_MONTHS = 12;

    /** Part des mois où un tiers doit apparaître pour être tenu pour récurrent. */
    private const float RECURRENCE_RATIO = 0.6;

    /** Nombre de secteurs détaillés du camembert, la traîne étant regroupée. */
    private const int DONUT_SLICES = 12;

    public function __construct(
        private StatisticsRepository $statisticsRepository,
        private OperationRepository $operationRepository,
        private PeriodResolver $periodResolver,
        private ChartBuilderInterface $chartBuilder,
    ) {
    }

    /**
     * Construit le tableau de bord complet.
     */
    public function build(Wallet $wallet, ?int $year = null, ?int $month = null): array
    {
        $boundaries = $this->statisticsRepository->boundaries($wallet);
        [$year, $month] = $this->resolveDefaultPeriod($boundaries, $year, $month);
        $years = $this->statisticsRepository->availableYears($wallet);
        $monthWindows = $this->periodResolver->monthOverMonth($year, $month);
        // Le comparatif annuel est ramené au début réel de l'historique, sans quoi
        // il opposerait un exercice complet à un exercice tronqué.
        $yearWindows = $this->periodResolver->alignToHistory(
            $this->periodResolver->yearOverYear($year),
            $boundaries['first']
        );

        $series = $this->series($wallet, $year, $month);

        return [
            'wallet' => $wallet,
            'selectedYear' => $year,
            'selectedMonth' => $month,
            'years' => $years,
            'months' => PeriodResolver::months(),
            'coverage' => $boundaries,
            'balance' => $this->balance($wallet),
            'month' => $this->periodStats($wallet, $monthWindows, $this->periodResolver->monthWindowLabel($month, $monthWindows['cutDay'])),
            'year' => $this->periodStats($wallet, $yearWindows, $yearWindows['current']->label),
            'series' => $series,
            'charts' => $this->charts($wallet, $series, $year, $month),
            // Totaux des périodes réellement portées par les camemberts : le
            // comparatif annuel travaille sur une fenêtre alignée, plus courte.
            'breakdownTotals' => [
                'month' => $this->statisticsRepository->totals($wallet, $this->periodResolver->month($year, $month))['expenses'],
                'year' => $this->statisticsRepository->totals($wallet, $this->periodResolver->year($year))['expenses'],
            ],
            'categories' => $this->categories($wallet, $monthWindows),
            'yearCategories' => $this->categories($wallet, $yearWindows),
            'beneficiaries' => $this->beneficiaries($wallet, $monthWindows),
            'largestExpenses' => $this->statisticsRepository->largestExpenses($wallet, $monthWindows['current'], 8),
            'incomeSources' => $this->statisticsRepository->incomesBySubCategory($wallet, $yearWindows['current']),
            'recurring' => $this->recurringCharges($wallet, $year, $month),
            'quality' => $this->statisticsRepository->dataQuality($wallet),
        ];
    }

    /**
     * Période affichée à défaut de demande explicite.
     *
     * Le mois courant peut n'avoir aucune écriture, le relevé s'arrêtant avant :
     * ouvrir le tableau de bord sur des colonnes vides n'apprend rien. On se
     * cale alors sur le dernier mois réellement alimenté.
     *
     * @param array{first: ?\DateTimeInterface, last: ?\DateTimeInterface} $boundaries
     *
     * @return array{0: int, 1: int}
     */
    private function resolveDefaultPeriod(array $boundaries, ?int $year, ?int $month): array
    {
        $today = $this->periodResolver->today();

        if (null !== $year && null !== $month) {
            return [$year, $month];
        }

        $last = $boundaries['last'] instanceof \DateTimeInterface
            ? \DateTimeImmutable::createFromInterface($boundaries['last'])
            : $today;

        $reference = $last < $today ? $last : $today;

        return [$year ?? (int) $reference->format('Y'), $month ?? (int) $reference->format('n')];
    }

    /**
     * Soldes courant, pointé et en-cours.
     */
    private function balance(Wallet $wallet): array
    {
        $current = round($this->operationRepository->sumBalance($wallet), 2);
        $pointed = round($this->operationRepository->sumBalance($wallet, true), 2);

        return [
            'current' => $current,
            'pointed' => $pointed,
            'pending' => round($current - $pointed, 2),
            'initial' => round((float) ($wallet->getInitialAmount() ?? 0.0), 2),
        ];
    }

    /**
     * Indicateurs d'une période et son comparatif à l'exercice précédent.
     *
     * @param array{current: Period, previous: Period, cutDay: int, partial: bool} $windows
     */
    private function periodStats(Wallet $wallet, array $windows, string $windowLabel): array
    {
        $current = $this->statisticsRepository->totals($wallet, $windows['current']);
        $previous = $this->statisticsRepository->totals($wallet, $windows['previous']);
        $hasReference = $previous['count'] > 0;

        $currentNet = round($current['incomes'] - $current['expenses'], 2);
        $previousNet = round($previous['incomes'] - $previous['expenses'], 2);
        $days = $windows['current']->days();

        return [
            'label' => $windows['current']->label,
            'windowLabel' => $windowLabel,
            'comparisonLabel' => $windows['previous']->label,
            // Libellés courts pour les en-têtes de colonnes : « Du 1er février au
            // 1 septembre 2026 » ne tient pas dans une colonne de tableau.
            'shortLabel' => $this->shortLabel($windows['current']),
            'shortComparisonLabel' => $this->shortLabel($windows['previous']),
            'partial' => $windows['partial'],
            'comparable' => $hasReference,
            'truncated' => $windows['truncated'] ?? false,
            'excludedMonths' => array_map(
                static fn (int $m): string => PeriodResolver::monthName($m),
                $windows['excludedMonths'] ?? []
            ),
            'incomes' => $current['incomes'],
            'expenses' => $current['expenses'],
            'net' => $currentNet,
            'operations' => $current['count'],
            'savingsRate' => $this->savingsRate($current['incomes'], $currentNet),
            'dailyExpenses' => round($current['expenses'] / max(1, $days), 2),
            'previous' => [
                'incomes' => $previous['incomes'],
                'expenses' => $previous['expenses'],
                'net' => $previousNet,
                'operations' => $previous['count'],
                'savingsRate' => $this->savingsRate($previous['incomes'], $previousNet),
            ],
            'trends' => [
                'incomes' => Comparison::between($current['incomes'], $previous['incomes'], $hasReference),
                'expenses' => Comparison::between($current['expenses'], $previous['expenses'], $hasReference),
                'net' => Comparison::between($currentNet, $previousNet, $hasReference),
                'savingsRate' => $hasReference
                    ? Comparison::betweenRates($this->savingsRate($current['incomes'], $currentNet), $this->savingsRate($previous['incomes'], $previousNet))
                    : Comparison::between(0, 0, false),
            ],
        ];
    }

    /**
     * Libellé court d'une fenêtre : l'année si elle en couvre une bonne part,
     * le mois sinon.
     */
    private function shortLabel(Period $period): string
    {
        if ($period->days() > 45) {
            return $period->start->format('Y');
        }

        return sprintf(
            '%s %s',
            PeriodResolver::monthName((int) $period->start->format('n')),
            $period->start->format('Y')
        );
    }

    /**
     * Taux d'épargne, indéfini en l'absence de revenus.
     */
    private function savingsRate(float $incomes, float $net): ?float
    {
        return $incomes > 0 ? round($net / $incomes * 100, 1) : null;
    }

    /**
     * Séries mensuelles sur douze mois glissants, avec le comparatif N-1.
     */
    private function series(Wallet $wallet, int $year, int $month): array
    {
        $periods = $this->periodResolver->trailingMonths($year, $month, self::SERIES_MONTHS);
        $first = $periods[0];
        $last = $periods[count($periods) - 1];

        $range = new Period($first->start->modify('-1 year'), $last->end, 'série');
        $totals = $this->statisticsRepository->monthlyTotals($wallet, $range);

        // Le solde de fin de mois se déduit du solde courant en remontant le temps :
        // inutile de rejouer l'historique complet depuis l'ouverture du compte.
        $balance = $this->operationRepository->sumBalance($wallet, false, $last->end);

        $points = [];
        foreach (array_reverse($periods) as $period) {
            $key = $period->start->format('Y-m');
            $data = $totals[$key] ?? ['incomes' => 0.0, 'expenses' => 0.0, 'count' => 0];
            $previousKey = $period->start->modify('-1 year')->format('Y-m');
            $previous = $totals[$previousKey] ?? null;

            $points[] = [
                'key' => $key,
                'label' => PeriodResolver::monthName((int) $period->start->format('n')),
                'shortLabel' => sprintf('%s %s', PeriodResolver::shortMonthName((int) $period->start->format('n')), $period->start->format('y')),
                'incomes' => $data['incomes'],
                'expenses' => $data['expenses'],
                'net' => round($data['incomes'] - $data['expenses'], 2),
                'operations' => $data['count'],
                'balance' => round($balance, 2),
                'previousExpenses' => $previous['expenses'] ?? null,
                'previousIncomes' => $previous['incomes'] ?? null,
                'current' => $key === sprintf('%04d-%02d', $year, $month),
            ];

            $balance -= round($data['incomes'] - $data['expenses'], 2);
        }

        return array_reverse($points);
    }

    /**
     * Graphiques Chart.js du tableau de bord.
     */
    private function charts(Wallet $wallet, array $series, int $year, int $month): array
    {
        $labels = array_column($series, 'shortLabel');

        $flow = $this->chartBuilder->createChart(Chart::TYPE_BAR);
        $flow->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'type' => 'bar',
                    'label' => 'Revenus',
                    'backgroundColor' => 'rgba(45, 189, 132, 0.75)',
                    'borderRadius' => 4,
                    'data' => array_column($series, 'incomes'),
                ],
                [
                    'type' => 'bar',
                    'label' => 'Dépenses',
                    'backgroundColor' => 'rgba(232, 87, 106, 0.75)',
                    'borderRadius' => 4,
                    'data' => array_column($series, 'expenses'),
                ],
                [
                    'type' => 'line',
                    'label' => 'Solde de fin de mois',
                    'borderColor' => 'rgba(88, 162, 217, 1)',
                    'backgroundColor' => 'rgba(88, 162, 217, 0.15)',
                    'borderWidth' => 2,
                    'tension' => 0.35,
                    'fill' => true,
                    'yAxisID' => 'balance',
                    'data' => array_column($series, 'balance'),
                ],
            ],
        ]);
        $flow->setOptions($this->baseOptions() + [
            'scales' => [
                'y' => ['position' => 'left'] + $this->axis(),
                'balance' => ['position' => 'right', 'grid' => ['drawOnChartArea' => false]] + $this->axis(),
                'x' => $this->axis(),
            ],
        ]);

        $comparison = $this->chartBuilder->createChart(Chart::TYPE_BAR);
        $comparison->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Dépenses N-1',
                    'backgroundColor' => 'rgba(140, 148, 164, 0.55)',
                    'borderRadius' => 4,
                    'data' => array_map(static fn (array $point): ?float => $point['previousExpenses'], $series),
                ],
                [
                    'label' => 'Dépenses',
                    'backgroundColor' => 'rgba(232, 87, 106, 0.85)',
                    'borderRadius' => 4,
                    'data' => array_column($series, 'expenses'),
                ],
            ],
        ]);
        $comparison->setOptions($this->baseOptions() + [
            'scales' => ['y' => $this->axis(), 'x' => $this->axis()],
        ]);

        return [
            'flow' => $flow,
            'comparison' => $comparison,
            'donut' => $this->donut($wallet, $this->periodResolver->year($year)),
            'donutMonth' => $this->donut($wallet, $this->periodResolver->month($year, $month)),
        ];
    }

    /**
     * Camembert de ventilation des dépenses sur une période.
     *
     * La ventilation est faite par sous-catégorie : « Tabac » ou « Frais
     * alimentaires » disent quelque chose, « Frais divers » ne dit rien.
     */
    private function donut(Wallet $wallet, Period $period): Chart
    {
        $breakdown = $this->statisticsRepository->expensesByCategory($wallet, $period);
        $bySubCategory = [];
        foreach ($breakdown as $row) {
            $name = $row['subCategoryName'] ?? 'Non classé';
            $bySubCategory[$name] = ($bySubCategory[$name] ?? 0) + $row['total'];
        }
        arsort($bySubCategory);

        // Au-delà d'une douzaine de secteurs le camembert n'est plus lisible :
        // la traîne est regroupée plutôt que tronquée, pour que le total tienne.
        $slices = array_slice($bySubCategory, 0, self::DONUT_SLICES, true);
        $tail = array_slice($bySubCategory, self::DONUT_SLICES, null, true);
        if ([] !== $tail) {
            $slices[sprintf('Autres (%d postes)', count($tail))] = array_sum($tail);
        }

        $donut = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $donut->setData([
            'labels' => array_keys($slices),
            'datasets' => [[
                'data' => array_map(static fn (float $value): float => round($value, 2), array_values($slices)),
                'backgroundColor' => $this->palette(count($slices)),
                'borderWidth' => 0,
            ]],
        ]);
        $donut->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '62%',
            'plugins' => [
                'legend' => ['position' => 'right', 'labels' => ['color' => '#c9d1de', 'boxWidth' => 12, 'padding' => 10]],
            ],
        ]);

        return $donut;

    }

    /**
     * Options communes aux graphiques cartésiens, accordées au thème sombre.
     */
    private function baseOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => ['mode' => 'index', 'intersect' => false],
            'plugins' => [
                'legend' => ['labels' => ['color' => '#c9d1de', 'boxWidth' => 12, 'padding' => 14]],
            ],
        ];
    }

    private function axis(): array
    {
        return [
            'ticks' => ['color' => '#8b93a3'],
            'grid' => ['color' => 'rgba(255, 255, 255, 0.06)'],
        ];
    }

    /**
     * @return string[]
     */
    private function palette(int $size): array
    {
        $colors = [
            '#e8576a', '#58a2d9', '#2dbd84', '#e2a33c', '#9b7fd4',
            '#3fb8b0', '#d9738f', '#7f9ac4', '#c98f5a', '#6fae5e', '#a8657f',
        ];

        return array_slice(array_merge(...array_fill(0, (int) ceil(max(1, $size) / count($colors)), $colors)), 0, max(1, $size));
    }

    /**
     * Ventilation par catégorie, avec l'écart par rapport à l'exercice précédent.
     *
     * @param array{current: Period, previous: Period} $windows
     */
    private function categories(Wallet $wallet, array $windows): array
    {
        $current = $this->statisticsRepository->expensesByCategory($wallet, $windows['current']);
        $previous = $this->statisticsRepository->expensesByCategory($wallet, $windows['previous']);
        $hasReference = [] !== $previous;

        $previousBySub = [];
        foreach ($previous as $row) {
            $previousBySub[$row['subCategoryId'] ?? 0] = $row['total'];
        }

        $total = array_sum(array_column($current, 'total'));
        $categories = [];

        foreach ($current as $row) {
            $categoryName = $row['categoryName'] ?? 'Non classé';
            $categories[$categoryName] ??= [
                'name' => $categoryName,
                'icon' => $row['categoryIcon'] ?? null,
                'total' => 0.0,
                'previousTotal' => 0.0,
                'count' => 0,
                'subCategories' => [],
            ];

            $previousTotal = $previousBySub[$row['subCategoryId'] ?? 0] ?? 0.0;
            $categories[$categoryName]['total'] += $row['total'];
            $categories[$categoryName]['previousTotal'] += $previousTotal;
            $categories[$categoryName]['count'] += $row['count'];
            $categories[$categoryName]['subCategories'][] = [
                'id' => $row['subCategoryId'],
                'name' => $row['subCategoryName'] ?? 'Non classé',
                'total' => $row['total'],
                'count' => $row['count'],
                'share' => $total > 0 ? round($row['total'] / $total * 100, 1) : 0.0,
                'trend' => Comparison::between($row['total'], $previousTotal, $hasReference),
            ];
        }

        foreach ($categories as $name => $category) {
            $categories[$name]['share'] = $total > 0 ? round($category['total'] / $total * 100, 1) : 0.0;
            $categories[$name]['trend'] = Comparison::between($category['total'], $category['previousTotal'], $hasReference);
            usort($categories[$name]['subCategories'], static fn (array $a, array $b): int => $b['total'] <=> $a['total']);
        }

        uasort($categories, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        // Écarts les plus contributeurs, tous postes confondus : c'est la lecture
        // qui explique un delta annuel, bien plus que le classement par montant.
        $deltas = [];
        foreach ($categories as $category) {
            if (abs($category['total'] - $category['previousTotal']) >= 1) {
                $deltas[] = [
                    'name' => $category['name'],
                    'current' => round($category['total'], 2),
                    'previous' => round($category['previousTotal'], 2),
                    'delta' => round($category['total'] - $category['previousTotal'], 2),
                ];
            }
        }
        usort($deltas, static fn (array $a, array $b): int => abs($b['delta']) <=> abs($a['delta']));

        // Part de chaque poste dans l'écart total, et échelle commune aux barres.
        $scale = 1.0;
        foreach ($deltas as $delta) {
            $scale = max($scale, abs($delta['delta']));
        }
        $deltas = array_map(static fn (array $delta): array => $delta + [
            'ratio' => round(abs($delta['delta']) / $scale * 100, 2),
        ], $deltas);

        // Liste à plat des postes, triée par montant : c'est la lecture directe,
        // sans avoir à déplier une catégorie pour atteindre le poste cherché.
        $posts = [];
        foreach ($categories as $category) {
            foreach ($category['subCategories'] as $subCategory) {
                $posts[] = $subCategory + ['category' => $category['name']];
            }
        }
        usort($posts, static fn (array $a, array $b): int => $b['total'] <=> $a['total']);

        $saved = 0.0;
        $overspent = 0.0;
        foreach ($deltas as $delta) {
            if ($delta['delta'] > 0) {
                $overspent += $delta['delta'];
            } else {
                $saved += abs($delta['delta']);
            }
        }

        return [
            'total' => round($total, 2),
            'items' => array_values($categories),
            'posts' => $posts,
            'deltas' => array_slice($deltas, 0, 10),
            'saved' => round($saved, 2),
            'overspent' => round($overspent, 2),
            'net' => round($overspent - $saved, 2),
            'comparable' => $hasReference,
        ];
    }

    /**
     * Top des bénéficiaires et leur évolution.
     *
     * @param array{current: Period, previous: Period} $windows
     */
    private function beneficiaries(Wallet $wallet, array $windows): array
    {
        $current = $this->statisticsRepository->expensesByBeneficiary($wallet, $windows['current'], 12);
        $previousRows = $this->statisticsRepository->expensesByBeneficiary($wallet, $windows['previous']);
        $hasReference = [] !== $previousRows;

        $previous = [];
        foreach ($previousRows as $row) {
            $previous[$row['name']] = $row['total'];
        }

        $max = $current[0]['total'] ?? 0.0;
        $items = array_map(static fn (array $row): array => $row + [
            'ratio' => $max > 0 ? round($row['total'] / $max * 100, 1) : 0.0,
            'average' => $row['count'] > 0 ? round($row['total'] / $row['count'], 2) : 0.0,
            'trend' => Comparison::between($row['total'], $previous[$row['name']] ?? 0.0, $hasReference),
        ], $current);

        return ['items' => $items, 'comparable' => $hasReference];
    }

    /**
     * Charges récurrentes estimées.
     *
     * Aucun champ ne marque la récurrence : elle est déduite de la régularité
     * d'apparition d'un tiers sur les douze derniers mois.
     */
    private function recurringCharges(Wallet $wallet, int $year, int $month): array
    {
        $periods = $this->periodResolver->trailingMonths($year, $month, self::SERIES_MONTHS);
        $observed = count($periods);
        $range = new Period($periods[0]->start, $periods[$observed - 1]->end, 'récurrence');
        $occurrences = $this->statisticsRepository->beneficiaryMonthlyTotals($wallet, $range);

        $items = [];
        $monthlyTotal = 0.0;

        foreach ($occurrences as $name => $amountsByMonth) {
            $months = count($amountsByMonth);
            if ($months < $observed * self::RECURRENCE_RATIO) {
                continue;
            }

            $amounts = array_values($amountsByMonth);
            sort($amounts);
            $median = $amounts[intdiv(count($amounts), 2)];
            $monthlyTotal += $median;
            $items[] = [
                'name' => $name,
                'months' => $months,
                'median' => round($median, 2),
                'yearly' => round($median * 12, 2),
            ];
        }

        usort($items, static fn (array $a, array $b): int => $b['median'] <=> $a['median']);

        return [
            'items' => array_slice($items, 0, 12),
            'count' => count($items),
            'monthlyTotal' => round($monthlyTotal, 2),
            'observedMonths' => $observed,
        ];
    }
}
