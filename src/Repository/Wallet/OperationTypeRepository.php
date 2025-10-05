<?php

declare(strict_types=1);

namespace App\Repository\Wallet;

use App\Entity\Wallet\OperationType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * OperationTypeRepository.
 *
 * @extends ServiceEntityRepository<OperationType>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class OperationTypeRepository extends ServiceEntityRepository
{
    /**
     * OperationTypeRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, OperationType::class);
    }
}
