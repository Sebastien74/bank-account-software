<?php

declare(strict_types=1);

namespace App\Repository\Security;

use App\Entity\Security\Group;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * GroupRepository.
 *
 * @extends ServiceEntityRepository<Group>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class GroupRepository extends ServiceEntityRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Group::class);
    }
}
