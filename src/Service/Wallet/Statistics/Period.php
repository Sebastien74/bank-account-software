<?php

declare(strict_types=1);

namespace App\Service\Wallet\Statistics;

/**
 * Period.
 *
 * Intervalle semi-ouvert [start, end[ exprimé en Europe/Paris.
 *
 * L'intervalle est volontairement semi-ouvert : borner une fin de période à
 * 23:59:59 laisse échapper les opérations horodatées dans la dernière seconde
 * et rend les périodes contiguës ambiguës.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class Period
{
    public function __construct(
        public \DateTimeImmutable $start,
        public \DateTimeImmutable $end,
        public string $label,
    ) {
    }

    /**
     * Nombre de jours entiers couverts par la période.
     */
    public function days(): int
    {
        return max(1, (int) $this->start->diff($this->end)->days);
    }

    /**
     * Période vide, utilisée lorsqu'aucune comparaison n'est possible.
     */
    public function isEmpty(): bool
    {
        return $this->start >= $this->end;
    }
}
