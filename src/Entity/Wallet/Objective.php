<?php

declare(strict_types=1);

namespace App\Entity\Wallet;

use App\Entity\BaseEntity;
use App\Repository\Wallet\ObjectiveRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Budget.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[ORM\Table(name: 'wallet_objective')]
#[ORM\Entity(repositoryClass: ObjectiveRepository::class)]
class Objective extends BaseEntity
{
    /**
     * Configurations.
     */
    protected static array $interface = [
        'name' => 'objective',
    ];
}
