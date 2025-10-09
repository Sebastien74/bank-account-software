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

        $subCategory = SubCategoryModel::fromEntity($operation->getSubCategory(), $coreLocator);

        return new self(
            id: $operation->getId(),
            entity: $operation,
            expense: $subCategory->expense,
            income: $subCategory->income,
            subCategory: $subCategory,
        );
    }
}