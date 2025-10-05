<?php

declare(strict_types=1);

namespace App\Entity\Wallet;

use App\Entity\BaseEntity;
use App\Repository\Wallet\BudgetRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Budget.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'wallet_budget')]
#[ORM\Entity(repositoryClass: BudgetRepository::class)]
class Budget extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'budget',
    ];
}
