<?php

declare(strict_types=1);

namespace App\Repository\Wallet;

use App\Entity\Wallet\Budget;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * BudgetRepository.
 *
 * @extends ServiceEntityRepository<Budget>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BudgetRepository extends ServiceEntityRepository
{
    /**
     * BudgetRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Budget::class);
    }
}
