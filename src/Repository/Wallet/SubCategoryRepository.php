<?php

declare(strict_types=1);

namespace App\Repository\Wallet;

use App\Entity\Wallet\SubCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * SubCategoryRepository.
 *
 * @extends ServiceEntityRepository<SubCategory>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SubCategoryRepository extends ServiceEntityRepository
{
    /**
     * SubCategoryRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, SubCategory::class);
    }
}
