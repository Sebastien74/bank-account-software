<?php

declare(strict_types=1);

namespace App\Entity\Wallet;

use App\Entity\BaseEntity;
use App\Repository\Wallet\OutsiderRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Outsider.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'wallet_outsider')]
#[ORM\Entity(repositoryClass: OutsiderRepository::class)]
class Outsider extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'outsider',
    ];
}
