<?php

declare(strict_types=1);

namespace App\Repository\Wallet;

use App\Entity\Wallet\Budget;
use App\Entity\Wallet\Wallet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * BudgetRepository.
 *
 * @extends ServiceEntityRepository<Budget>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BudgetRepository extends ServiceEntityRepository
{
    /**
     * BudgetRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Budget::class);
    }

    /**
     * Enveloppes d'un compte, celles qui portent sur un poste.
     *
     * Une enveloppe sans poste ne peut être confrontée à rien. Elle est tout de
     * même renvoyée : la masquer laisserait croire qu'elle n'existe pas, alors
     * qu'il lui manque seulement son rattachement.
     *
     * @return Budget[]
     */
    public function forWallet(Wallet $wallet): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.subCategories', 'sc')
            ->addSelect('sc')
            ->andWhere('b.wallet = :wallet')
            ->setParameter('wallet', $wallet)
            ->orderBy('b.amount', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
