<?php

declare(strict_types=1);

namespace App\Model\Wallet;

use App\Entity\Wallet\SubCategory;
use App\Model\BaseModel;
use App\Service\CoreLocatorInterface;

/**
 * SubCategoryModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class SubCategoryModel extends BaseModel
{
    /**
     * SubCategoryModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?object $entity = null,
        public readonly ?bool $expense = null,
        public readonly ?bool $income = null,
    ) {
    }

    /**
     * fromEntity.
     */
    public static function fromEntity(?SubCategory $subCategory, CoreLocatorInterface $coreLocator, array $options = []): object
    {
        self::setLocator($coreLocator);

        return new self(
            id: $subCategory?->getId(),
            entity: $subCategory,
            expense: $subCategory && 'expenses' === $subCategory->getType(),
            income: $subCategory && 'incomes' === $subCategory->getType(),
        );
    }
}
