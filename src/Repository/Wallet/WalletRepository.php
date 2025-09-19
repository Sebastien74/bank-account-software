<?php

declare(strict_types=1);

namespace App\Repository\Wallet;

use App\Entity\Wallet\Wallet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * WalletRepository.
 *
 * @extends ServiceEntityRepository<Wallet>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class WalletRepository extends ServiceEntityRepository
{
    /**
     * WalletRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Wallet::class);
    }
}
