<?php

declare(strict_types=1);

namespace App\Enum\Wallet;

/**
 * OperationDirection.
 *
 * Sens d'une opération. La valeur correspond à la chaîne stockée en base dans
 * OperationType::$type et SubCategory::$type.
 *
 * Le montant d'une opération est toujours positif : c'est le sens qui porte le
 * signe. Cette règle était réimplémentée à six endroits avec trois replis
 * différents ; elle n'existe plus qu'ici.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
enum OperationDirection: string
{
    case Expenses = 'expenses';
    case Incomes = 'incomes';

    /**
     * Résout le sens à partir des deux colonnes de typage.
     *
     * Le type d'opération prime, à défaut celui de la sous-catégorie, à défaut
     * une dépense.
     */
    public static function resolve(?string $operationTypeType, ?string $subCategoryType): self
    {
        return self::tryFrom((string) $operationTypeType)
            ?? self::tryFrom((string) $subCategoryType)
            ?? self::Expenses;
    }

    /**
     * Signe algébrique appliqué au montant pour calculer un solde.
     */
    public function sign(): int
    {
        return self::Incomes === $this ? 1 : -1;
    }

    public function label(): string
    {
        return self::Incomes === $this ? 'Revenus' : 'Dépenses';
    }
}
