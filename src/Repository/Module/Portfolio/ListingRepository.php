<?php

declare(strict_types=1);

namespace App\Repository\Module\Portfolio;

use App\Entity\Module\Portfolio\Listing;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * ListingRepository.
 *
 * @extends ServiceEntityRepository<Listing>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ListingRepository extends ServiceEntityRepository
{
    /**
     * ListingRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Listing::class);
    }
}
