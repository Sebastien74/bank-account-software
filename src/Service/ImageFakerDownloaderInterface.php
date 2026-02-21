<?php

declare(strict_types=1);

namespace App\Service;

/**
 * ImageFakerDownloaderInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface ImageFakerDownloaderInterface
{
    public function download(string $url, string $targetDir = 'public/medias/faker', ?string $filename = null, ?string $filenameNotFound = null): ?string;
}
