<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\CoreLocatorInterface;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * SecurityRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SecurityRuntime implements RuntimeExtensionInterface
{
    /**
     * SecurityRuntime constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Is granted.
     */
    public function granted(string $roleName): bool
    {
        return $this->coreLocator->granted($roleName);
    }
}
