<?php

declare(strict_types=1);

namespace App\Form\Manager\Wallet;

use App\Entity\Wallet\Operation;
use App\Entity\Wallet\Wallet;
use Symfony\Component\Form\FormInterface;

/**
 * OperationInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface OperationInterface
{
    /**
     * Importe un relevé de compte XLSX et retourne un rapport d'exécution.
     */
    public function import(?string $filename = null, ?Wallet $wallet = null, bool $dryRun = false): array;

    /**
     * Décompose un libellé bancaire en nature d'opération et bénéficiaire.
     *
     * @return array{nature: string, merchant: string, middle: string}
     */
    public function parseLabel(string $rawLabel): array;

    /**
     * Détermine le slug de la sous-catégorie d'une opération.
     *
     * @param array{nature: string, merchant: string, middle: string} $parsed
     */
    public function resolveSubCategorySlug(string $rawLabel, array $parsed, bool $isExpense): ?string;

    public function execute(Operation $operation, FormInterface $form): void;
}
