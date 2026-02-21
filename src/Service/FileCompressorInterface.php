<?php

declare(strict_types=1);

namespace App\Service;

/**
 * FileCompressorInterface.
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
interface FileCompressorInterface
{
    public function compress(string $dirname, ?int $maxDimension = null, ?int $maxFilesize = null): string;
}
