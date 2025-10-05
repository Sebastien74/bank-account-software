<?php

declare(strict_types=1);

namespace App\Repository\Wallet;

use App\Entity\Wallet\Objective;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ObjectiveRepository.
 *
 * @extends ServiceEntityRepository<Objective>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ObjectiveRepository extends ServiceEntityRepository
{
    /**
     * ObjectiveRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Objective::class);
    }
}
