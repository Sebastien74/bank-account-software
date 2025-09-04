<?php

declare(strict_types=1);

namespace App\Service\Core;

use Symfony\Component\String\Slugger\AsciiSlugger;

/**
 * Urlizer.
 *
 * To slugify string.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class Urlizer
{
    /**
     * Static method to slugify a string
     */
    public static function urlize(?string $string = null): ?string
    {
        if (!is_string($string)) {
            return $string;
        }

        return (new AsciiSlugger())
            ->slug($string)
            ->lower()
            ->toString();
    }
}