<?php

declare(strict_types=1);

namespace App\Repository\Wallet;

use App\Entity\Wallet\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * CategoryRepository.
 *
 * @extends ServiceEntityRepository<Category>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CategoryRepository extends ServiceEntityRepository
{
    /**
     * CategoryRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Category::class);
    }
}
