<?php

declare(strict_types=1);

namespace App\Service\Wallet\Statistics;

/**
 * PeriodResolver.
 *
 * Construit les fenêtres de comparaison alignées entre deux exercices.
 *
 * Toute la difficulté d'un comparatif bancaire tient dans les périodes
 * incomplètes : comparer un mois d'août arrêté au 15 avec un mois d'août entier
 * produit une baisse artificielle de moitié. Le jour de coupe est donc calculé
 * une seule fois, ici, et appliqué aux deux fenêtres.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class PeriodResolver
{
    private const string TIMEZONE = 'Europe/Paris';

    private const array MONTHS = [
        1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
        9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
    ];

    /**
     * Abréviations distinctes : tronquer à trois lettres confondrait juin et juillet.
     */
    private const array SHORT_MONTHS = [
        1 => 'Janv', 2 => 'Févr', 3 => 'Mars', 4 => 'Avr',
        5 => 'Mai', 6 => 'Juin', 7 => 'Juil', 8 => 'Août',
        9 => 'Sept', 10 => 'Oct', 11 => 'Nov', 12 => 'Déc',
    ];

    public function __construct(private ?\DateTimeImmutable $today = null)
    {
    }

    public function timezone(): \DateTimeZone
    {
        return new \DateTimeZone(self::TIMEZONE);
    }

    public function today(): \DateTimeImmutable
    {
        return $this->today ?? new \DateTimeImmutable('today', $this->timezone());
    }

    /**
     * @return array<int, string>
     */
    public static function months(): array
    {
        return self::MONTHS;
    }

    public static function monthName(int $month): string
    {
        return self::MONTHS[$month] ?? '';
    }

    public static function shortMonthName(int $month): string
    {
        return self::SHORT_MONTHS[$month] ?? '';
    }

    /**
     * Mois complet, du 1er au 1er du mois suivant.
     */
    public function month(int $year, int $month): Period
    {
        $start = $this->at($year, $month, 1);

        return new Period($start, $start->modify('first day of next month'), sprintf('%s %d', self::monthName($month), $year));
    }

    /**
     * Année complète.
     */
    public function year(int $year): Period
    {
        $start = $this->at($year, 1, 1);

        return new Period($start, $start->modify('+1 year'), (string) $year);
    }

    /**
     * Couple de fenêtres alignées pour comparer un mois d'une année sur l'autre.
     *
     * Le jour de coupe vaut le jour courant si le mois est en cours, sinon le
     * dernier jour du mois. Il est ensuite ramené au plus petit des deux mois
     * comparés : un 29 février n'a pas d'équivalent l'année précédente.
     *
     * @return array{current: Period, previous: Period, cutDay: int, partial: bool}
     */
    public function monthOverMonth(int $year, int $month): array
    {
        $today = $this->today();
        $isCurrentMonth = (int) $today->format('Y') === $year && (int) $today->format('n') === $month;

        $lastDayCurrent = (int) $this->at($year, $month, 1)->format('t');
        $lastDayPrevious = (int) $this->at($year - 1, $month, 1)->format('t');

        $cutDay = $isCurrentMonth ? (int) $today->format('j') : $lastDayCurrent;
        $cutDay = min($cutDay, $lastDayCurrent, $lastDayPrevious);

        return [
            'current' => $this->window($year, $month, $cutDay),
            'previous' => $this->window($year - 1, $month, $cutDay),
            'cutDay' => $cutDay,
            'partial' => $cutDay < $lastDayCurrent,
        ];
    }

    /**
     * Couple de fenêtres alignées pour comparer deux années.
     *
     * @return array{current: Period, previous: Period, cutMonth: int, cutDay: int, partial: bool}
     */
    public function yearOverYear(int $year): array
    {
        $today = $this->today();
        $isCurrentYear = (int) $today->format('Y') === $year;

        $cutMonth = $isCurrentYear ? (int) $today->format('n') : 12;
        $cutDay = $isCurrentYear ? (int) $today->format('j') : 31;
        $cutDay = min(
            $cutDay,
            (int) $this->at($year, $cutMonth, 1)->format('t'),
            (int) $this->at($year - 1, $cutMonth, 1)->format('t')
        );

        return [
            'current' => new Period(
                $this->at($year, 1, 1),
                $this->at($year, $cutMonth, $cutDay)->modify('+1 day'),
                sprintf('Du 1er janvier au %s %s %d', $cutDay, mb_strtolower(self::monthName($cutMonth)), $year)
            ),
            'previous' => new Period(
                $this->at($year - 1, 1, 1),
                $this->at($year - 1, $cutMonth, $cutDay)->modify('+1 day'),
                sprintf('Du 1er janvier au %s %s %d', $cutDay, mb_strtolower(self::monthName($cutMonth)), $year - 1)
            ),
            'cutMonth' => $cutMonth,
            'cutDay' => $cutDay,
            'partial' => $isCurrentYear,
        ];
    }

    /**
     * Aligne un couple de fenêtres annuelles sur le début réel de l'historique.
     *
     * Un compte dont les écritures ne commencent qu'en février n'a pas de janvier
     * à opposer : comparer huit mois à sept fait apparaître une hausse qui n'existe
     * pas. Les deux fenêtres sont donc ramenées au même mois de départ, et les mois
     * écartés sont remontés pour être affichés.
     *
     * @param array{current: Period, previous: Period, cutMonth: int, cutDay: int, partial: bool} $windows
     *
     * @return array{current: Period, previous: Period, cutMonth: int, cutDay: int, partial: bool, excludedMonths: int[], truncated: bool}
     */
    public function alignToHistory(array $windows, ?\DateTimeInterface $firstOperation): array
    {
        $windows['excludedMonths'] = [];
        $windows['truncated'] = false;

        if (!$firstOperation instanceof \DateTimeInterface) {
            return $windows;
        }

        $previousStart = $windows['previous']->start;
        $first = \DateTimeImmutable::createFromInterface($firstOperation)->setTimezone($this->timezone());

        if ($first <= $previousStart) {
            return $windows;
        }

        // Un mois entamé en cours de route ne se compare pas non plus : on démarre
        // au premier mois complet.
        $startMonth = (int) $first->format('n');
        $startYear = (int) $first->format('Y');
        if ('1' !== $first->format('j')) {
            $next = $first->modify('first day of next month');
            $startMonth = (int) $next->format('n');
            $startYear = (int) $next->format('Y');
        }

        if ($startYear > (int) $previousStart->format('Y')) {
            // L'historique ne couvre pas du tout l'exercice de référence.
            return $windows;
        }

        for ($month = 1; $month < $startMonth; ++$month) {
            $windows['excludedMonths'][] = $month;
        }

        if ([] === $windows['excludedMonths']) {
            return $windows;
        }

        $windows['truncated'] = true;
        $windows['current'] = $this->rebase($windows['current'], $startMonth);
        $windows['previous'] = $this->rebase($windows['previous'], $startMonth);

        return $windows;
    }

    /**
     * Repositionne le début d'une fenêtre sur le premier jour d'un mois donné.
     */
    private function rebase(Period $period, int $month): Period
    {
        $start = $period->start->setDate((int) $period->start->format('Y'), $month, 1);
        $lastDay = $period->end->modify('-1 day');

        return new Period($start, $period->end, sprintf(
            'Du 1er %s au %s %s %s',
            mb_strtolower(self::monthName($month)),
            $lastDay->format('j'),
            mb_strtolower(self::monthName((int) $lastDay->format('n'))),
            $start->format('Y')
        ));
    }

    /**
     * Les douze mois glissants s'achevant à la fin du mois demandé.
     *
     * @return Period[]
     */
    public function trailingMonths(int $year, int $month, int $count = 12): array
    {
        $cursor = $this->at($year, $month, 1)->modify(sprintf('-%d months', $count - 1));
        $periods = [];

        for ($index = 0; $index < $count; ++$index) {
            $periods[] = $this->month((int) $cursor->format('Y'), (int) $cursor->format('n'));
            $cursor = $cursor->modify('first day of next month');
        }

        return $periods;
    }

    /**
     * Libellé littéral d'une fenêtre de comparaison mensuelle.
     */
    public function monthWindowLabel(int $month, int $cutDay): string
    {
        $name = mb_strtolower(self::monthName($month));

        return 1 === $cutDay
            ? sprintf('Le 1er %s', $name)
            : sprintf('Du 1er au %d %s', $cutDay, $name);
    }

    private function window(int $year, int $month, int $cutDay): Period
    {
        $start = $this->at($year, $month, 1);

        return new Period(
            $start,
            $this->at($year, $month, $cutDay)->modify('+1 day'),
            sprintf('%s %d', self::monthName($month), $year)
        );
    }

    private function at(int $year, int $month, int $day): \DateTimeImmutable
    {
        return (new \DateTimeImmutable('now', $this->timezone()))
            ->setDate($year, $month, $day)
            ->setTime(0, 0);
    }
}
