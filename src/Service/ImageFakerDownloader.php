<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;

/**
 * ImageFakerDownloader.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ImageFakerDownloader implements ImageFakerDownloaderInterface
{
    private ?string $backgroundColorHex = '#cccccc';
    private ?string $textColorHex = '#929292';

    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Generate a placeholder image from a tag like "fakeimg-360x500"
     */
    public function download(
        string $url,
        string $targetDir = 'public/medias/faker',
        ?string $filename = null,
        ?string $filenameNotFound = null,
    ): ?string {

        if ($url && str_contains($url, 'https')) {
            return null;
        }

        $isNotFound = $url && str_contains($url, 'notfound');
        $url = $isNotFound ? str_replace('notfound', '', $url) : $url;
        $backgroundColorHex = $this->backgroundColorHex;
        $textColorHex = $this->textColorHex;

        if (str_contains($url, '?')) {
            $matches = explode('?', $url);
            $url = $matches[0];
            $params = [];
            $paramsMatches = explode('&', $matches[1]);
            foreach ($paramsMatches as $param) {
                $matches = explode('=', $param);
                $params[$matches[0]] = $matches[1];
            }
            $backgroundColorHex = !empty($params['bg']) ? $params['bg'] : $backgroundColorHex;
            $textColorHex = !empty($params['color']) ? $params['color'] : $textColorHex;
        }

        if (!preg_match('/^fakeimg-(\d+)x(\d+)$/', $url, $matches)) {
            return null;
        }

        $filesystem = new Filesystem();

        $projectDir = $this->coreLocator->projectDir();
        $extension = 'jpg';
        $filenameConfig = str_replace('#', '', $backgroundColorHex.'-'.$textColorHex);

        if ($filename === null) {
            $filename = $url.'-'.$filenameConfig;
        } else {
            $filename = pathinfo($filename, PATHINFO_FILENAME).'.'.$filenameConfig;
        }
        $filename = $isNotFound ? 'notfound-'.$filename.'.'.$extension : $filename.'.'.$extension;
        $fileDirname = $this->coreLocator->formatDirname($projectDir.'/'.$targetDir.'/'.$filename);

        if ($filesystem->exists($fileDirname)) {
            return $fileDirname;
        }

        $width = intval($matches[1]) * 2;
        $height = intval($matches[2]) * 2;

        $image = imagecreatetruecolor($width, $height);

        [$bgR, $bgG, $bgB] = $this->hexToRgb($backgroundColorHex);
        [$txtR, $txtG, $txtB] = $this->hexToRgb($textColorHex);

        $bgColor = imagecolorallocate($image, $bgR, $bgG, $bgB);
        $textColor = imagecolorallocate($image, $txtR, $txtG, $txtB);

        imagefilledrectangle($image, 0, 0, $width, $height, $bgColor);

        $text = $isNotFound ? 'Not found' : sprintf('%dx%d', $width, $height);
        $fontFile = $this->coreLocator->formatDirname($projectDir.'/assets/lib/fonts/Ardoise/Ardoise-Bold.ttf');

        $fontBaseRatio = 0.12;
        $maxFontSize = (int)floor(min($width, $height) * $fontBaseRatio);
        $minFontSize = 8;
        $targetRatio = 0.8;
        $fontSize = $minFontSize;
        for ($size = $maxFontSize; $size >= $minFontSize; --$size) {
            $bbox = imagettfbbox($size, 0, $fontFile, $text);
            $textWidth = $bbox[2] - $bbox[0];
            $textHeight = $bbox[1] - $bbox[7];
            if ($textWidth <= $width * $targetRatio && $textHeight <= $height * $targetRatio) {
                $fontSize = $size;
                break;
            }
        }

        $bbox = imagettfbbox($fontSize, 0, $fontFile, $text);
        $textWidth = $bbox[2] - $bbox[0];
        $textHeight = $bbox[1] - $bbox[7];

        $x = (int)(($width - $textWidth) / 2);
        $y = (int)(($height + $textHeight) / 2);

        imagettftext($image, $fontSize, 0, $x, $y, $textColor, $fontFile, $text);

        $absoluteTargetDir = rtrim(
            $this->coreLocator->formatDirname($this->coreLocator->projectDir().'/'.$targetDir),
            DIRECTORY_SEPARATOR
        );

        $filesystem->mkdir($absoluteTargetDir);

        $absolutePath = $absoluteTargetDir.DIRECTORY_SEPARATOR.$filename;

        imagejpeg($image, $absolutePath, 80);
        imagedestroy($image);

        return $fileDirname;
    }

    /**
     * Convert hex color to RGB components
     *
     * @param string $hex
     *
     * @return array{0:int,1:int,2:int}
     */
    private function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = sprintf('%s%s%s%s%s%s',
                $hex[0],
                $hex[0],
                $hex[1],
                $hex[1],
                $hex[2],
                $hex[2]
            );
        }

        $int = hexdec($hex);

        return [
            ($int >> 16) & 255,
            ($int >> 8) & 255,
            $int & 255,
        ];
    }
}
