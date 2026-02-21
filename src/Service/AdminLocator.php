<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * AdminLocator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => AdminLocator::class, 'key' => 'admin_locator'],
])]
class AdminLocator implements AdminLocatorInterface
{
    private array $cache = [];
}
