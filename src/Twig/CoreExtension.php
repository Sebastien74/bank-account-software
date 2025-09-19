<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\CoreLocatorInterface;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * CoreExtension
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
readonly class CoreExtension implements RuntimeExtensionInterface
{
    public function __construct(private CoreLocatorInterface $coreLocator)
    {
    }

    public function routeArgs(?string $route = null, mixed $entity = null, array $parameters = []): array
    {
        return $this->coreLocator->routeArgs($route, $entity, $parameters);
    }
}