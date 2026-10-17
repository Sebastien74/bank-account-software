<?php

declare(strict_types=1);

namespace App\Repository\Wallet;

use App\Entity\Wallet\RecurringCharge;
use App\Entity\Wallet\Wallet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * RecurringChargeRepository.
 *
 * @extends ServiceEntityRepository<RecurringCharge>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RecurringChargeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecurringCharge::class);
    }

    /**
     * Réglages du compte, indexés par identifiant de charge.
     *
     * @return array<string, RecurringCharge>
     */
    public function settingsFor(Wallet $wallet): array
    {
        $settings = [];
        foreach ($this->findBy(['wallet' => $wallet]) as $charge) {
            $settings[(string) $charge->getSlug()] = $charge;
        }

        return $settings;
    }
}
