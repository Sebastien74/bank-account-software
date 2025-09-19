<?php

declare(strict_types=1);

namespace App\Repository\Wallet;

use App\Entity\Wallet\Operation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * OperationRepository.
 *
 * @extends ServiceEntityRepository<Operation>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class OperationRepository extends ServiceEntityRepository
{
    /**
     * OperationRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Operation::class);
    }
}
