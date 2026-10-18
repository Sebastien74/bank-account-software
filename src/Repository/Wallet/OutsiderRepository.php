<?php

declare(strict_types=1);

namespace App\Repository\Wallet;

use App\Entity\Wallet\Outsider;
use App\Service\Urlizer;
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

    /**
     * Bénéficiaires dont le nom contient la recherche.
     *
     * Le slug est interrogé en plus du nom : il est sans accents ni casse,
     * si bien que « cafe » retrouve « Café des Sports ».
     *
     * @return Outsider[]
     */
    public function search(string $query): array
    {
        $escape = static fn (string $value): string => addcslashes($value, '%_\\');
        $qb = $this->createQueryBuilder('o')
            ->andWhere('o.adminName LIKE :name')
            ->setParameter('name', '%'.$escape($query).'%')
            ->orderBy('o.adminName', 'ASC');

        // Une recherche faite de seuls symboles donne un slug vide, qui
        // ramènerait tous les bénéficiaires.
        $slug = (string) Urlizer::urlize($query);
        if ('' !== $slug) {
            $qb->orWhere('o.slug LIKE :slug')->setParameter('slug', '%'.$escape($slug).'%');
        }

        return $qb->getQuery()->getResult();
    }
}
