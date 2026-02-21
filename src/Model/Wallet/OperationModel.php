<?php

declare(strict_types=1);

namespace App\Model\Wallet;

use App\Entity\Wallet\Operation;
use App\Model\BaseModel;
use App\Service\CoreLocatorInterface;

/**
 * OperationModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class OperationModel extends BaseModel
{
    /**
     * OperationModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?Operation $entity = null,
        public readonly ?bool $expense = null,
        public readonly ?bool $income = null,
        public readonly ?SubCategoryModel $subCategory = null,
    ) {
    }

    /**
     * fromEntity.
     */
    public static function fromEntity(Operation $operation, CoreLocatorInterface $coreLocator, array $options = []): object
    {
        self::setLocator($coreLocator);

        $subCategoryEntity = $operation->getOperationType();
        $subCategory = SubCategoryModel::fromEntity($subCategoryEntity, $coreLocator);

        $operationType = $operation->getOperationType();
        $isExpense = false;
        $isIncome = false;

        if ($operationType) {
            $isExpense = 'expenses' === $operationType->getType();
            $isIncome = 'incomes' === $operationType->getType();
        } elseif ($subCategoryEntity) {
            $isExpense = $subCategory->expense;
            $isIncome = $subCategory->income;
        }

        return new self(
            id: $operation->getId(),
            entity: $operation,
            expense: $isExpense,
            income: $isIncome,
            subCategory: $subCategory,
        );
    }
}
