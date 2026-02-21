<?php

declare(strict_types=1);

namespace App\Service;

/**
 * BrowserDetectionInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface BrowserDetectionInterface
{
    function getBrowser(): ?string;

    function isTablet(): bool;

    function isMobile(): bool;

    function is(string $key, string $userAgent = null, array $httpHeaders = null): bool;
}
