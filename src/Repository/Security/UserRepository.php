<?php

declare(strict_types=1);

namespace App\Repository\Security;

use App\Entity\Security\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * UserRepository.
 *
 * @extends ServiceEntityRepository<User>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UserRepository extends ServiceEntityRepository
{
    /**
     * UserRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, User::class);
    }

    /**
     * Find User by identifier.
     *
     * @throws NonUniqueResultException
     */
    public function loadUserByIdentifier(string $identifier): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :identifier')
            ->orWhere('u.login = :identifier')
            ->setParameter('identifier', $identifier)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
