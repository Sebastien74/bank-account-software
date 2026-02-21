<?php

declare(strict_types=1);

namespace App\Service;

/**
 * FileCompressor.
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
class FileCompressor implements FileCompressorInterface
{
    private const int MAX_FILE_DIMENSION = 3840; // pixels
    private const int MAX_FILE_SIZE = 500 * 1024; // 500 Ko

    public function compress(string $dirname, ?int $maxDimension = null, ?int $maxFilesize = null): string
    {
        return $this->optimizeImageAttachment(
            $dirname,
            is_numeric($maxDimension) && $maxDimension > 0 ? $maxDimension : self::MAX_FILE_DIMENSION,
            is_numeric($maxFilesize) && $maxFilesize > 0 ? $maxFilesize : self::MAX_FILE_SIZE
        );
    }

    /**
     * Optimize image attachment (resize + compress) in place.
     */
    private function optimizeImageAttachment(string $dirname, int $maxDimension, int $maxFilesize): string
    {
        // Basic checks
        if (!is_file($dirname) || !is_readable($dirname) || !is_writable($dirname)) {
            return $dirname;
        }

        // Get image metadata
        [$width, $height, $type] = @getimagesize($dirname);
        if (!$width || !$height || !$type) {
            return $dirname;
        }

        $mime = image_type_to_mime_type($type);

        // Create image resource from file
        switch ($mime) {
            case 'image/jpeg':
                $srcImage = @imagecreatefromjpeg($dirname);
                break;
            case 'image/png':
                $srcImage = @imagecreatefrompng($dirname);
                if ($srcImage) {
                    imagealphablending($srcImage, true);
                    imagesavealpha($srcImage, true);
                }
                break;
            default:
                // Unsupported format for optimization
                return $dirname;
        }

        if (!$srcImage) {
            return $dirname;
        }

        // Current working size (may change with additional downscales)
        $currentWidth  = $width;
        $currentHeight = $height;

        // --- 1) First: clamp to maxDimension (no upscaling) ---------------------

        $ratio = min($maxDimension / $width, $maxDimension / $height, 1);

        if ($ratio < 1) {

            $currentWidth  = (int) round($width * $ratio);
            $currentHeight = (int) round($height * $ratio);

            $dstImage = imagecreatetruecolor($currentWidth, $currentHeight);

            if ($mime === 'image/png') {
                // Preserve transparency for PNG
                imagealphablending($dstImage, false);
                imagesavealpha($dstImage, true);
                $transparent = imagecolorallocatealpha($dstImage, 0, 0, 0, 127);
                imagefilledrectangle($dstImage, 0, 0, $currentWidth, $currentHeight, $transparent);
            }

            imagecopyresampled(
                $dstImage,
                $srcImage,
                0,
                0,
                0,
                0,
                $currentWidth,
                $currentHeight,
                $width,
                $height
            );

            imagedestroy($srcImage);
            $srcImage = $dstImage;
        }

        // --- 2) Write and ensure file size <= $maxFilesize ----------------------

        if ($mime === 'image/jpeg') {
            $this->saveJpegUnderMaxSize($srcImage, $dirname, $maxFilesize, $currentWidth, $currentHeight);
        } elseif ($mime === 'image/png') {
            $this->savePngUnderMaxSize($srcImage, $dirname, $maxFilesize, $currentWidth, $currentHeight);
        }

        imagedestroy($srcImage);

        return $dirname;
    }


    /**
     * Save JPEG image under a given max filesize by decreasing quality (and if needed resizing down).
     */
    private function saveJpegUnderMaxSize($image, string $dirname, int $maxFilesize, int $width, int $height): void
    {
        $minQuality = 10;   // lowest allowed quality
        $step       = 10;   // step to decrease quality
        $scaleStep  = 0.85; // if still too big, scale image down by this factor

        $currentWidth  = $width;
        $currentHeight = $height;

        // We'll allow a few resize passes maximum to avoid infinite loops
        for ($resizePass = 0; $resizePass < 5; $resizePass++) {

            $quality = 90;

            // Try quality loop first
            while (true) {
                imagejpeg($image, $dirname, $quality);
                clearstatcache(true, $dirname);
                $filesize = filesize($dirname);

                if ($filesize <= $maxFilesize || $quality <= $minQuality) {
                    break;
                }

                $quality -= $step;
            }

            if ($filesize <= $maxFilesize) {
                return;
            }

            // Still too big -> downscale further and retry
            // Avoid going below some minimum size to keep something usable
            if ($currentWidth < 300 && $currentHeight < 300) {
                return;
            }

            $newWidth  = (int) round($currentWidth * $scaleStep);
            $newHeight = (int) round($currentHeight * $scaleStep);

            $dstImage = imagecreatetruecolor($newWidth, $newHeight);

            imagecopyresampled(
                $dstImage,
                $image,
                0,
                0,
                0,
                0,
                $newWidth,
                $newHeight,
                $currentWidth,
                $currentHeight
            );

            imagedestroy($image);
            $image         = $dstImage;
            $currentWidth  = $newWidth;
            $currentHeight = $newHeight;
        }
    }

    /**
     * Save PNG image under a given max filesize by resizing down progressively.
     */
    private function savePngUnderMaxSize($image, string $dirname, int $maxFilesize, int $width, int $height): void
    {
        $compressionLevel = 9;
        $scaleStep        = 0.85; // each pass: reduce size to 85% of previous

        $currentWidth  = $width;
        $currentHeight = $height;

        // Try some passes of downscaling until under limit or too small
        for ($resizePass = 0; $resizePass < 8; $resizePass++) {

            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagepng($image, $dirname, $compressionLevel);

            clearstatcache(true, $dirname);
            $filesize = filesize($dirname);

            if ($filesize <= $maxFilesize) {
                return;
            }

            // Still too big -> downscale further
            if ($currentWidth < 300 && $currentHeight < 300) {
                return; // don't go smaller than that
            }

            $newWidth  = (int) round($currentWidth * $scaleStep);
            $newHeight = (int) round($currentHeight * $scaleStep);

            $dstImage = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency
            imagealphablending($dstImage, false);
            imagesavealpha($dstImage, true);
            $transparent = imagecolorallocatealpha($dstImage, 0, 0, 0, 127);
            imagefilledrectangle($dstImage, 0, 0, $newWidth, $newHeight, $transparent);

            imagecopyresampled(
                $dstImage,
                $image,
                0,
                0,
                0,
                0,
                $newWidth,
                $newHeight,
                $currentWidth,
                $currentHeight
            );

            imagedestroy($image);
            $image         = $dstImage;
            $currentWidth  = $newWidth;
            $currentHeight = $newHeight;
        }
    }
}
