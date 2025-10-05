<?php

declare(strict_types=1);

namespace App\Repository\Wallet;

use App\Entity\Wallet\Outsider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * OutsiderRepository.
 *
 * @extends ServiceEntityRepository<Outsider>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class OutsiderRepository extends ServiceEntityRepository
{
    /**
     * OutsiderRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Outsider::class);
    }
}
