<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\CoreLocatorInterface;
use App\Service\FileInfoInterface;
use App\Service\ImageFakerDownloaderInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * MediaRuntime
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
class MediaRuntime implements RuntimeExtensionInterface
{
    private bool $packageExist = false;

    /**
     * MediaRuntime constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly FileInfoInterface $fileInfo,
        private readonly Environment $templating,
        private readonly Filesystem $filesystem,
        private readonly Packages $packages,
        private readonly ImageFakerDownloaderInterface $imageFakerDownloader,
    ) {

    }

    /**
     * To render image.
     *
     * @throws LoaderError|RuntimeError|SyntaxError
     */
    public function img(string $filename, ?string $packageName = null, array $options = []): ?string
    {
        $options['lazyLoad'] = isset($options['lazyLoad']) && true === (bool)$options['lazyLoad'];
        $fileInfo = $this->sourceInfo($filename, 'images', $packageName, $options);

        if (!empty($options['style']) && true === (bool)$options['style']) {
            return $this->getAttributeStyle($fileInfo, $options);
        }

        $options['generateThumb'] = $fileInfo->generateThumb();
        $options['files'] = $files = $fileInfo->files();
        $options['width'] = property_exists($files, 'width') ? $files->width : null;
        $options['height'] = property_exists($files, 'height') ? $files->height : null;
        $options['extension'] = $fileInfo->extension();
        $options['isAllowedRuntime'] = $fileInfo->isAllowedRuntime();
        $options['screensSizes'] = $fileInfo->screensSizes();
        $options['alt'] = isset($options['alt']) && $options['alt'] ? $options['alt'] : $fileInfo->filename();
        $options['attributes'] = $this->getAttributes($fileInfo, $options);
        $options['lazyFileSvg'] = property_exists($files, 'lazyFileSvg') && $files->lazyFileSvg ? $options['files']->lazyFileSvg
            : 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';

        echo $this->templating->render('core/image.html.twig', $options);

        return null;
    }

    /**
     * To get source info.
     */
    private function sourceInfo(string $filename, string $type, string $packageName, array $options = []): FileInfoInterface
    {
        $filesystem = new Filesystem();

        $screensSizes = $this->fileInfo->screensSizes($options);
        $dirname = $this->dirname($filename, $type, $packageName, $options);
        if (!$filesystem->exists($dirname)) {
            $dirname = $this->dirname('fakeimgnotfound-'.$screensSizes->desktopWidth.'x'.$screensSizes->desktopHeight, $type, $packageName, $options);
        }

        $options['screensMedias'] = !empty($options['screensMedias']) ? $options['screensMedias'] : [];
        foreach ($options['screensMedias'] as $screen => $screenFilename) {
            $options['screensMedias'][$screen] = $this->dirname($screenFilename, $type, $packageName, $options);
            if (!$filesystem->exists($options['screensMedias'][$screen])) {
                $widthProperty = $screen.'Width';
                $heightProperty = $screen.'Height';
                if (property_exists($screensSizes, $widthProperty) && property_exists($screensSizes, $heightProperty)) {
                    $options['screensMedias'][$screen] = $this->dirname('fakeimgnotfound-'.$screensSizes->$widthProperty.'x'.$screensSizes->$heightProperty, $type, $packageName, $options);
                }
            }
        }
        if (empty($options['screensMedias']['desktop'])) {
            $options['screensMedias']['desktop'] = $dirname;
        }

        return $this->fileInfo->file($filename, $dirname, $options);
    }

    /**
     * Get attributes style.
     */
    private function getAttributeStyle(FileInfoInterface $fileInfo, array $options): string
    {
        $style = [];
        $hexadecimalBg = $options['hexadecimalBg'] ?? '';

        foreach (['desktop', 'tablet', 'mobile'] as $screen) {
            $property = $screen.'Retina';
            if (property_exists($fileInfo->files(), $property)) {
                $style[] = ['screen' => $screen, 'style' => 'background:'.trim($hexadecimalBg.' url("'.$fileInfo->files()->$property.'");')];
            }
        }

        return json_encode($style);
    }

    /**
     * Get attributes.
     */
    private function getAttributes(FileInfoInterface $fileInfo, array $options): string
    {
        $alt = isset($options['alt']) && $options['alt'] ? $options['alt'] : $fileInfo->filename();
        $width = isset($options['width']) && $options['width'] ? intval($options['width']) : false;
        $height = isset($options['height']) && $options['height'] ? intval($options['height']) : false;
        $lazyLoad = isset($options['lazyLoad']) && true === (bool)$options['lazyLoad'];
        $customClass = isset($options['class']) && $options['class'] ? $options['class'] : false;
        $priority = !$lazyLoad ? 'high' : 'low';
        $attributes = '';

        $id = $options['id'] ?? false;
        if ($id) {
            $attributes .= 'id="'.trim($id).'" ';
        }

        $class = !empty($options['class']) ? $options['class'].' ' : '';
        $class .= 'img-fluid img-'.$fileInfo->extension().' ';
        $class .= $lazyLoad ? 'lazy-load ' : '';
        $class .= $customClass ? $customClass.' ' : '';

        $attributes .= 'class="'.trim($class).'" ';
        $attributes .= $alt ? 'alt="'.$alt.'" ' : '';
        $attributes .= is_int($width) && $width > 0 ? 'width="'.$width.'" ' : '';
        $attributes .= is_int($height) && $height > 0 ? 'height="'.$height.'" ' : '';
        $attributes .= $priority !== 'high' && $lazyLoad ? 'loading="lazy" ' : ($priority !== 'high' ? ' loading="eager" ' : 'decoding="async" loading="eager" ');
        $attributes .= 'fetchpriority="'.$priority.'" ';

        $sizes = [];
        foreach (['desktop', 'tablet', 'mobile'] as $screen) {
            $propertyWidth = $screen.'Width';
            $propertyHeight = $screen.'Height';
            if (property_exists($fileInfo->screensSizes(), $propertyWidth) && property_exists($fileInfo->screensSizes(), $propertyHeight)) {
                $sizes[] = ['screen' => $screen, 'width' => $fileInfo->screensSizes()->$propertyWidth, 'height' => $fileInfo->screensSizes()->$propertyHeight];
            }
        }
        if (!empty($sizes)) {
            $attributes .= "data-img-sizes='".json_encode($sizes)."'";
        }

        return trim($attributes);
    }

    /**
     * To set dirname.
     */
    private function dirname(string $filename, string $type, string $packageName, array $options = []): ?string
    {
        $packageExist = false;

        if (str_contains($filename, 'fakeimg')) {

            $dirname = $this->imageFakerDownloader->download($filename);

        } else {

            $baseDirname = $this->coreLocator->formatDirname($this->coreLocator->projectDir().'/assets/medias/'.$type.'/'.$packageName.'/');
            $dirname = $this->coreLocator->formatDirname($baseDirname.trim($filename, '/'));
            $exist = $packageExist = $this->filesystem->exists($dirname);

            if (!$exist) {
                $baseDirname = $this->coreLocator->formatDirname($this->coreLocator->publicDir().'/medias/');
                $dirname = $this->coreLocator->formatDirname($baseDirname.trim($filename, '/'));
                $exist = $this->filesystem->exists($dirname);
            }

            if (!$exist && isset($options['placeholder']) && true === (bool)$options['placeholder']) {
                $dirname = $this->coreLocator->formatDirname($this->coreLocator->publicDir().'/medias/placeholder.jpg');
            }
        }

        if ($dirname && $packageExist) {
            $packageDirname = trim($this->packages->getUrl('build/'.$packageName.'/'.$type.'/'.$filename, $packageName), '/');
            $dirname = $this->coreLocator->formatDirname($this->coreLocator->publicDir().'/'.$packageDirname);
        }

        if ($dirname && str_contains($dirname, $this->coreLocator->formatDirname($this->coreLocator->projectDir().'/assets/'))) {
            $dirname = $this->coreLocator->formatDirname($this->coreLocator->publicDir().'/medias/placeholder.jpg');
        }

        return $dirname;
    }
}
