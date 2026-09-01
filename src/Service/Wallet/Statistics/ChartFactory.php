<?php

declare(strict_types=1);

namespace App\Service\Wallet\Statistics;

/**
 * ChartFactory.
 *
 * Projette des séries de valeurs en géométrie SVG prête à interpoler.
 *
 * Aucun calcul ne doit subsister dans le template : toutes les coordonnées sont
 * produites ici, et surtout formatées avec un point décimal. La locale française
 * écrirait « 12,5 » dans un attribut « d », ce qui casse silencieusement le tracé.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class ChartFactory
{
    private const int WIDTH = 1000;
    private const int HEIGHT = 260;
    private const int PADDING_TOP = 12;
    private const int PADDING_BOTTOM = 26;

    /**
     * Courbe simple, éventuellement doublée d'une série de comparaison.
     *
     * @param array<int, array{label: string, value: float}> $series
     * @param array<int, array{label: string, value: float}> $comparison
     */
    public function line(array $series, array $comparison = []): array
    {
        if ([] === $series) {
            return ['empty' => true];
        }

        $values = array_column($series, 'value');
        $comparisonValues = array_column($comparison, 'value');
        $all = array_merge($values, $comparisonValues);
        $min = min(0.0, min($all));
        $max = max($all);
        $max = $max > $min ? $max : $min + 1;

        $points = $this->project($series, $min, $max);
        $comparisonPoints = [] !== $comparison ? $this->project($comparison, $min, $max) : [];

        return [
            'empty' => false,
            'width' => self::WIDTH,
            'height' => self::HEIGHT,
            'path' => $this->path($points),
            'areaPath' => $this->areaPath($points),
            'comparisonPath' => [] !== $comparisonPoints ? $this->path($comparisonPoints) : null,
            'points' => $points,
            'gridLines' => $this->gridLines($min, $max),
            'zeroLine' => $min < 0 ? $this->format($this->toY(0.0, $min, $max)) : null,
            'min' => $min,
            'max' => $max,
        ];
    }

    /**
     * Barres appariées : une série courante et sa comparaison, groupées par label.
     *
     * @param array<int, array{label: string, current: float, previous: float, comparable: bool}> $groups
     */
    public function pairedBars(array $groups): array
    {
        if ([] === $groups) {
            return ['empty' => true];
        }

        $max = 0.0;
        foreach ($groups as $group) {
            $max = max($max, $group['current'], $group['previous']);
        }
        $max = $max > 0 ? $max : 1.0;

        $usable = self::HEIGHT - self::PADDING_TOP - self::PADDING_BOTTOM;
        $slot = self::WIDTH / count($groups);
        $barWidth = $slot * 0.32;
        $bars = [];

        foreach (array_values($groups) as $index => $group) {
            $left = $slot * $index;
            foreach ([['previous', $left + $slot * 0.12], ['current', $left + $slot * 0.5]] as [$key, $x]) {
                $height = $max > 0 ? ($group[$key] / $max) * $usable : 0.0;
                $bars[] = [
                    'serie' => $key,
                    'x' => $this->format($x),
                    'y' => $this->format(self::PADDING_TOP + $usable - $height),
                    'width' => $this->format($barWidth),
                    'height' => $this->format(max(0.0, $height)),
                    'value' => $group[$key],
                    'label' => $group['label'],
                    'comparable' => $group['comparable'],
                ];
            }
        }

        return [
            'empty' => false,
            'width' => self::WIDTH,
            'height' => self::HEIGHT,
            'bars' => $bars,
            'labels' => $this->axisLabels(array_column($groups, 'label'), $slot),
            'gridLines' => $this->gridLines(0.0, $max),
            'max' => $max,
        ];
    }

    /**
     * Barres divergentes autour d'un axe central : écarts positifs et négatifs.
     *
     * @param array<int, array{label: string, value: float}> $rows
     */
    public function divergingBars(array $rows): array
    {
        if ([] === $rows) {
            return ['empty' => true];
        }

        $scale = 0.0;
        foreach ($rows as $row) {
            $scale = max($scale, abs($row['value']));
        }
        $scale = $scale > 0 ? $scale : 1.0;

        $bars = [];
        foreach ($rows as $row) {
            $ratio = abs($row['value']) / $scale * 50;
            $bars[] = [
                'label' => $row['label'],
                'value' => $row['value'],
                'x' => $this->format($row['value'] >= 0 ? 50 : 50 - $ratio),
                'width' => $this->format($ratio),
                'positive' => $row['value'] >= 0,
            ];
        }

        return ['empty' => false, 'bars' => $bars, 'scale' => $scale];
    }

    /**
     * Barre horizontale simple exprimée en pourcentage de la valeur maximale.
     *
     * @param array<int, array{label: string, value: float}> $rows
     */
    public function bars(array $rows): array
    {
        $max = 0.0;
        foreach ($rows as $row) {
            $max = max($max, abs($row['value']));
        }
        $max = $max > 0 ? $max : 1.0;

        return array_map(fn (array $row): array => $row + [
            'ratio' => $this->format(abs($row['value']) / $max * 100),
        ], $rows);
    }

    /**
     * Découpe une valeur en six paliers, pour la carte de chaleur.
     *
     * Une couleur calculée au rendu imposerait un attribut de style porteur de
     * donnée : on renvoie un indice de palier, la couleur reste dans le SCSS.
     */
    public function heatLevel(float $value, float $max): int
    {
        if ($max <= 0 || $value <= 0) {
            return 0;
        }

        return max(1, min(5, (int) ceil($value / $max * 5)));
    }

    /**
     * @param array<int, array{label: string, value: float}> $series
     */
    private function project(array $series, float $min, float $max): array
    {
        $count = count($series);
        $step = $count > 1 ? self::WIDTH / ($count - 1) : 0.0;
        $points = [];

        foreach (array_values($series) as $index => $item) {
            $points[] = [
                'x' => $this->format($count > 1 ? $step * $index : self::WIDTH / 2),
                'y' => $this->format($this->toY($item['value'], $min, $max)),
                'value' => $item['value'],
                'label' => $item['label'],
            ];
        }

        return $points;
    }

    private function toY(float $value, float $min, float $max): float
    {
        $usable = self::HEIGHT - self::PADDING_TOP - self::PADDING_BOTTOM;
        $span = $max - $min;

        return self::PADDING_TOP + $usable - ($span > 0 ? ($value - $min) / $span * $usable : 0.0);
    }

    private function path(array $points): string
    {
        $commands = [];
        foreach ($points as $index => $point) {
            $commands[] = sprintf('%s%s,%s', 0 === $index ? 'M' : 'L', $point['x'], $point['y']);
        }

        return implode(' ', $commands);
    }

    private function areaPath(array $points): string
    {
        if ([] === $points) {
            return '';
        }

        $baseline = $this->format(self::HEIGHT - self::PADDING_BOTTOM);
        $first = $points[0];
        $last = $points[count($points) - 1];

        return sprintf(
            'M%s,%s %s L%s,%s Z',
            $first['x'],
            $baseline,
            $this->path($points),
            $last['x'],
            $baseline
        );
    }

    private function gridLines(float $min, float $max): array
    {
        $lines = [];
        for ($step = 0; $step <= 4; ++$step) {
            $value = $min + ($max - $min) * $step / 4;
            $lines[] = [
                'y' => $this->format($this->toY($value, $min, $max)),
                'value' => round($value, 0),
            ];
        }

        return $lines;
    }

    private function axisLabels(array $labels, float $slot): array
    {
        $positions = [];
        foreach (array_values($labels) as $index => $label) {
            $positions[] = ['x' => $this->format($slot * $index + $slot / 2), 'label' => $label];
        }

        return $positions;
    }

    /**
     * Sérialise une coordonnée avec un point décimal, quelle que soit la locale.
     */
    private function format(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
