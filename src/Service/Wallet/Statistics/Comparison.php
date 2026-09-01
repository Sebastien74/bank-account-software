<?php

declare(strict_types=1);

namespace App\Service\Wallet\Statistics;

/**
 * Comparison.
 *
 * Résultat d'une comparaison entre deux périodes alignées.
 *
 * Le statut porte l'information la plus importante : un écart en pourcentage
 * n'a de sens que si la base de comparaison existe et n'est pas dérisoire.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final readonly class Comparison
{
    /** Comparaison exploitable, écart en euros et en pourcentage. */
    public const string COMPARABLE = 'comparable';
    /** Aucune donnée sur la période de référence. */
    public const string UNAVAILABLE = 'unavailable';
    /** Base de référence nulle alors que la période courante est alimentée. */
    public const string NEW = 'new';
    /** Base de référence trop faible pour qu'un pourcentage veuille dire quelque chose. */
    public const string INSIGNIFICANT = 'insignificant';

    /** Seuil en dessous duquel un pourcentage d'écart n'est pas publié. */
    private const float LOW_BASE_THRESHOLD = 50.0;

    public function __construct(
        public float $current,
        public float $previous,
        public float $delta,
        public ?float $percent,
        public string $status,
    ) {
    }

    /**
     * Construit une comparaison à partir des deux valeurs mesurées.
     *
     * @param bool $referenceHasData la période de référence contient-elle des opérations,
     *                               indépendamment du montant mesuré sur cet axe
     */
    public static function between(float $current, float $previous, bool $referenceHasData = true): self
    {
        $delta = round($current - $previous, 2);

        if (!$referenceHasData) {
            return new self($current, $previous, $delta, null, self::UNAVAILABLE);
        }

        if (abs($previous) < 0.005) {
            // Un poste absent l'an dernier est « nouveau », pas « +100 % ».
            return new self($current, $previous, $delta, null, abs($current) < 0.005 ? self::UNAVAILABLE : self::NEW);
        }

        if (abs($previous) <= self::LOW_BASE_THRESHOLD) {
            return new self($current, $previous, $delta, null, self::INSIGNIFICANT);
        }

        return new self($current, $previous, $delta, round($delta / abs($previous) * 100, 1), self::COMPARABLE);
    }

    /**
     * Écart en points, pour les grandeurs déjà exprimées en pourcentage.
     *
     * Comparer deux taux en pourcentage de pourcentage n'a pas de sens : le taux
     * d'épargne passant de 5 % à 10 % gagne 5 points, pas 100 %.
     */
    public static function betweenRates(?float $current, ?float $previous): self
    {
        if (null === $current || null === $previous) {
            return new self($current ?? 0.0, $previous ?? 0.0, 0.0, null, self::UNAVAILABLE);
        }

        return new self($current, $previous, round($current - $previous, 1), null, self::COMPARABLE);
    }

    public function isPublishable(): bool
    {
        return self::UNAVAILABLE !== $this->status;
    }
}
