<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * VendorExtension.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class VendorExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('routeArgs', [CoreExtension::class, 'routeArgs']),
            new TwigFilter('icon', [IconRuntime::class, 'icon']),
            new TwigFilter('img', [MediaRuntime::class, 'img']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('routeArgs', [CoreExtension::class, 'routeArgs']),
            new TwigFunction('icon', [IconRuntime::class, 'icon']),
            new TwigFunction('img', [MediaRuntime::class, 'img']),
        ];
    }
}
