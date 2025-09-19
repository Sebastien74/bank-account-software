<?php

declare(strict_types=1);

namespace App\Repository\Wallet;

use App\Entity\Wallet\CategoryType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CategoryTypeRepository.
 *
 * @extends ServiceEntityRepository<CategoryType>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CategoryTypeRepository extends ServiceEntityRepository
{
    /**
     * CategoryTypeRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, CategoryType::class);
    }
}
