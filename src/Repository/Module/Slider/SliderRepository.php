<?php

declare(strict_types=1);

namespace App\Repository\Module\Slider;

use App\Entity\Module\Slider\Slider;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * SliderRepository.
 *
 * @extends ServiceEntityRepository<Slider>
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SliderRepository extends ServiceEntityRepository
{
    /**
     * SliderRepository constructor.
     */
    public function __construct(private readonly ManagerRegistry $registry)
    {
        parent::__construct($this->registry, Slider::class);
    }
}
