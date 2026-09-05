<?php

declare(strict_types=1);

namespace App\Service;

use App\Twig\BrowserRuntime;
use Liip\ImagineBundle\Service\FilterService;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

/**
 * FileInfo.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FileInfo implements FileInfoInterface
{
    private const int MAX_FILE_DIMENSION = 3840; // pixels
    private const int MAX_FILE_SIZE = 500 * 1024; // 500 Ko
    private const array SCREENS = ['desktop', 'tablet', 'mobile'];
    private const array SCREENS_SIZES = [
        'desktop' => 1920,
        'tablet' => 991,
        'mobile' => 618,
    ];
    private const array RUNTIME_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    private ?string $screen = null;
    private ?string $dirname = null;
    private ?string $extension = null;
    private ?string $filename = null;
    private ?string $path = null;
    private ?string $attributes = null;
    private ?string $mime = null;
    private ?int $size = null;
    private ?int $width = null;
    private ?int $height = null;
    private ?array $filesCache = null;
    private ?object $screensSizes = null;
    private ?array $screensFiles = null;
    private ?array $screensFilesExtensions = null;
    private ?object $runtimeConfigs = null;
    private ?object $files = null;
    private ?int $bits = null;
    private bool $generateThumb = false;
    private ?string $formatBytes = null;
    private bool $isImage = false;
    private bool $isPlaceHolder = false;

    /**
     * FileInfo constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly FilterService $filterService,
        private readonly BrowserRuntime $browserRuntime,
        private readonly FileCompressorInterface $fileCompressor,
    )
    {
        $this->screen = $this->coreLocator->request() instanceof Request && !$this->screen ? $this->browserRuntime->screen() : 'desktop';
    }

    public function file(?string $filename = null, ?string $dirname = null, array $options = []): static
    {
        $this->resetValues();

        if ($dirname && !is_dir($dirname) && (new Filesystem())->exists($dirname)) {
            $sizes = getimagesize($dirname);
            $file = new File($dirname);
            $infos = new SplFileInfo($dirname, $file->getPathname(), $file->getFilename());
            $this->dirname = $dirname;
            $this->filename = $infos->getFilename();
            $this->extension = $infos->getExtension();
            $this->path = str_replace([$this->coreLocator->projectDir().DIRECTORY_SEPARATOR.'public', DIRECTORY_SEPARATOR], ['', '/'], $this->dirname);
            $this->attributes = !empty($sizes[3]) ? $sizes[3] : null;
            $this->mime = !empty($sizes['mime']) ? $sizes['mime'] : null;
            $this->size = $infos->getSize();
            $this->width = !empty($sizes[0]) ? $sizes[0] : null;
            $this->height = !empty($sizes[1]) ? $sizes[1] : null;
            $this->bits = !empty($sizes['bits']) ? $sizes['bits'] : null;
            $this->isImage = @is_array($sizes);
//            $this->generateThumb = (isset($options['generateThumb']) && $options['generateThumb']) || !in_array($this->extension, self::RUNTIME_EXTENSIONS);
            $this->generateThumb = true;
            $this->filesCache();
            $this->setScreensFiles($options);
            $this->setScreensSizes($options);
            $this->setRuntimeConfigs();
            $this->setFiles();
            $this->setWebLink($options);
            $this->setFormatBytes($this->size);
        } else {
            $this->files = (object)[];
            $this->screensSizes = (object)[];
        }

        return $this;
    }

    /**
     * To get dirname.
     */
    public function dirname(): ?string
    {
        return $this->dirname;
    }

    /**
     * To get extension.
     */
    public function extension(): ?string
    {
        return $this->extension;
    }

    /**
     * To get filename.
     */
    public function filename(): ?string
    {
        return $this->filename;
    }

    /**
     * To get path.
     */
    public function path(): ?string
    {
        return $this->path;
    }

    /**
     * To get attributes.
     */
    public function attributes(): ?string
    {
        return $this->attributes;
    }

    /**
     * To get mime.
     */
    public function mime(): ?string
    {
        return $this->mime;
    }

    /**
     * To get size.
     */
    public function size(): ?int
    {
        return $this->size;
    }

    /**
     * To get width.
     */
    public function width(): ?int
    {
        return $this->width;
    }

    /**
     * To get height.
     */
    public function height(): ?int
    {
        return $this->height;
    }

    /**
     * To get screensSizes.
     */
    public function screensSizes(array $options = []): object
    {
        if (!empty($options)) {
            $this->setScreensSizes($options);
        }

        return $this->screensSizes;
    }

    /**
     * To get files.
     */
    public function files(): object
    {
        return $this->files;
    }

    /**
     * To get screens files.
     */
    public function screensFiles(array $options = []): object
    {
        return (object)$this->screensFiles;
    }

    /**
     * To get bits.
     */
    public function bits(): ?int
    {
        return $this->bits;
    }

    /**
     * To get generateThumb.
     */
    public function generateThumb(): bool
    {
        return $this->generateThumb;
    }

    /**
     * To get isImage.
     */
    public function isImage(): bool
    {
        return $this->isImage;
    }

    /**
     * To get isAllowedRuntime.
     */
    public function isAllowedRuntime(): bool
    {
        return $this->extension && in_array($this->extension, self::RUNTIME_EXTENSIONS);
    }

    /**
     * To get isPlaceHolder.
     */
    public function isPlaceHolder(): bool
    {
        return $this->isPlaceHolder;
    }

    /**
     * To get formatBytes.
     */
    public function formatBytes($bytes = null, int $precision = 2): ?string
    {
        return $this->formatBytes;
    }

    /**
     * To get screensSizes.
     */
    private function setScreensSizes(array $options = []): void
    {
        $config = [];
        $screensSizes = !empty($options['screensSizes']) ? $options['screensSizes'] : [];

        foreach (self::SCREENS as $screen) {
            $config[$screen.'Width'] = !empty($screensSizes[$screen]['width']) ? $screensSizes[$screen]['width'] : null;
            $config[$screen.'Height'] = !empty($screensSizes[$screen]['height']) ? $screensSizes[$screen]['height'] : null;
        }

        $config['tabletWidth'] = !$config['tabletWidth'] ? $config['desktopWidth'] : $config['tabletWidth'];
        $config['tabletHeight'] = !$config['tabletHeight'] ? $config['desktopHeight'] : $config['tabletHeight'];
        $config['mobileWidth'] = !$config['mobileWidth'] ? $config['tabletWidth'] : $config['mobileWidth'];
        $config['mobileHeight'] = !$config['mobileHeight'] ? $config['tabletHeight'] : $config['mobileHeight'];

        foreach (self::SCREENS as $screen) {
            if (!empty($options['width'])) {
                $config[$screen.'Width'] = !empty($screensSizes[$screen]['width']) ? $screensSizes[$screen]['width'] : null;
            }
            if (!empty($options['height'])) {
                $config[$screen.'Height'] = !empty($screensSizes[$screen]['height']) ? $screensSizes[$screen]['height'] : null;
            }
            if (empty($config[$screen.'Width']) && empty($config[$screen.'Height'])) {
                $sizes = [];
                if (!empty($this->screensFiles[$screen])) {
                    $dirname = $this->coreLocator->formatDirname($this->coreLocator->publicDir().$this->screensFiles[$screen]);
                    $sizes = (new Filesystem())->exists($dirname) ? getimagesize($dirname) : [];
                }
                $config[$screen.'Width'] = !empty($sizes[0]) ? $sizes[0] : $this->width;
                $config[$screen.'Height'] = !empty($sizes[1]) ? $sizes[1] : $this->height;
            }
            $svgSizes = 'svg' === $this->extension ? $this->svgSizes($config[$screen.'Width'], $config[$screen.'Height']) : false;
            if (empty($config[$screen.'Width']) && !empty($config[$screen.'Height']) && $this->height) {
                $config[$screen.'Width'] = $svgSizes ? $svgSizes->width : (int) ceil(($this->width * $config[$screen.'Height']) / $this->height);
            }
            if (!empty($config[$screen.'Width']) && empty($config[$screen.'Height']) && $this->width) {
                $config[$screen.'Height'] = $svgSizes ? $svgSizes->height : (int) ceil(($this->height * $config[$screen.'Width']) / $this->width);
            }
        }

        $this->screensSizes = (object) $config;
    }

    /**
     * To get screensFiles.
     */
    private function setScreensFiles(array $options = []): void
    {
        $files = $screensDirnames = $extensions = [];

        $screensMediasOptions = !empty($options['screensMedias']) ? $options['screensMedias'] : [];
        $screensMedias['mobile'] = !empty($screensMediasOptions['mobile']) ? $screensMediasOptions['mobile'] : (!empty($screensMediasOptions['tablet']) ? $screensMediasOptions['tablet'] : $this->dirname);
        $screensMedias['tablet'] = !empty($screensMediasOptions['tablet']) ? $screensMediasOptions['tablet'] : (!empty($screensMediasOptions['desktop']) ? $screensMediasOptions['desktop'] : $this->dirname);
        $screensMedias['desktop'] = !empty($screensMediasOptions['desktop']) ? $screensMediasOptions['desktop'] : $this->dirname;

        foreach (self::SCREENS as $screen) {
            if (!empty($screensMedias[$screen])) {
                $files[$screen] = str_replace([$this->coreLocator->projectDir().DIRECTORY_SEPARATOR.'public', DIRECTORY_SEPARATOR], ['', '/'], $screensMedias[$screen]);
                $files[$screen.'Retina'] = str_replace([$this->coreLocator->projectDir().DIRECTORY_SEPARATOR.'public', DIRECTORY_SEPARATOR], ['', '/'], $screensMedias[$screen]);
                $screensDirnames[$screen] = $screensMedias[$screen];
                $matches = explode('.', $files[$screen]);
                $extensions[$screen] = end($matches);
            }
        }

        $this->screensFiles = $files;
        $this->screensFilesExtensions = $extensions;

        foreach ($this->screensFiles as $screen => $file) {
            $dirname = $this->coreLocator->formatDirname($this->coreLocator->projectDir().'/public'.$file);
            $file = new File($dirname);
            $fileSize = $file->getSize();
            $imageSize = @getimagesize($file->getPathname());
            $width  = $imageSize !== false ? $imageSize[0] : null;
            if (
                ($width !== null && $width > self::MAX_FILE_DIMENSION) ||
                ($fileSize !== false && $fileSize > self::MAX_FILE_SIZE)
            ) {
                $dirname = $this->fileCompressor->compress($file->getPathname(), self::MAX_FILE_DIMENSION, self::MAX_FILE_SIZE);
                $this->screensFiles[$screen] = str_replace([$this->coreLocator->projectDir().DIRECTORY_SEPARATOR.'public', DIRECTORY_SEPARATOR], ['', '/'], $dirname);
            }
        }
    }

    /**
     * To set RuntimeConfigs.
     */
    private function setRuntimeConfigs(): void
    {
        $runtimeConfigs = [];
        $screensSizes = is_object($this->screensSizes) ? (array)$this->screensSizes : [];

        foreach (self::SCREENS_SIZES as $screen => $maxWidth) {
            $screenWidth = !empty($screensSizes[$screen.'Width']) ? $screensSizes[$screen.'Width'] : null;
            $screenHeight = !empty($screensSizes[$screen.'Height']) ? $screensSizes[$screen.'Height'] : null;
            if ($screenWidth && $screenWidth > $maxWidth) {
                $screenHeight = (int) ceil(($screenHeight * $maxWidth) / $screenWidth);
                $screenWidth = $maxWidth;
            }
            $runtimeConfigs[$screen]['upscale']['min'] = [$screenWidth, $screenHeight];
            $runtimeConfigs[$screen]['thumbnail']['size'] = [$screenWidth, $screenHeight];
            $runtimeConfigs[$screen]['thumbnail']['mode'] = 'outbound';
            $runtimeConfigs[$screen.'Retina']['upscale']['min'] = [$screenWidth * 2, $screenHeight * 2];
            $runtimeConfigs[$screen.'Retina']['thumbnail']['size'] = [$screenWidth * 2, $screenHeight * 2];
            $runtimeConfigs[$screen.'Retina']['thumbnail']['mode'] = 'outbound';
        }

        $this->runtimeConfigs = (object) $runtimeConfigs;
    }

    /**
     * To get RuntimeConfigs.
     */
    public function runtimeConfigs(): object
    {
        return $this->runtimeConfigs;
    }

    /**
     * To set files.
     */
    private function setFiles(): void
    {
        $files = [];
        $files['width'] = $this->width;
        $files['height'] = $this->height;

        $runtimeConfigs = is_object($this->runtimeConfigs) ? (array)$this->runtimeConfigs : [];

        foreach ($runtimeConfigs as $screen => $runtimeConfig) {
            $path = !empty($this->screensFiles[$screen]) ? $this->screensFiles[$screen] : $this->path;
            $files[$screen] = $this->fileCache($path, $runtimeConfig, 'media100');
            $extension = !empty($this->screensFilesExtensions[$screen]) ? $this->screensFilesExtensions[$screen] : $this->extension;
            if (!$files[$screen]) {
                $file = !in_array($extension, self::RUNTIME_EXTENSIONS) || !$this->generateThumb ? $path
                    : $this->filterService->getUrlOfFilteredImageWithRuntimeFilters(str_replace('.webp', '', $path), 'media100', $runtimeConfig);
                $files[$screen] = !in_array($extension, self::RUNTIME_EXTENSIONS) ? $file : $this->generateWebp($file, 'media100', $screen);
                $this->setFileCache($path, $runtimeConfig, $files[$screen], 'media100');
            }
        }

        $lazyPath = !empty($files[$this->screen]) ? $files[$this->screen] : null;
        if (!empty($runtimeConfigs[$this->screen])) {
            $extension = !empty($this->screensFilesExtensions[$this->screen]) ? $this->screensFilesExtensions[$this->screen] : $this->extension;
            $files['lazy'] = $this->fileCache($lazyPath, $runtimeConfigs[$this->screen], 'media1');
            if (!$files['lazy']) {
                $file = !in_array($extension, self::RUNTIME_EXTENSIONS) || !$this->generateThumb ? $lazyPath :
                    $this->filterService->getUrlOfFilteredImageWithRuntimeFilters(str_replace('.webp', '.'.$extension, $lazyPath), 'media1', $runtimeConfigs[$this->screen]);
                $files['lazy'] = !in_array($extension, self::RUNTIME_EXTENSIONS) || !$this->generateThumb ? $file : $this->generateWebp($file, 'media1', $this->screen);
                $this->setFileCache($lazyPath, $runtimeConfigs[$this->screen], $files['lazy'], 'media1');
            }
        }

        foreach ($files as $screen => $path) {
            if (!in_array($screen, ['width', 'height']) && $path && !str_contains($path, $this->coreLocator->schemeAndHttpHost())) {
                $files[$screen] = $this->coreLocator->schemeAndHttpHost().$path;
            }
        }

        $files['lazy'] = !empty($files['lazy']) ? $files['lazy'] : "data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==";
        $files['source'] = !empty($files[$this->screen]) ? $files[$this->screen] : $this->path;
        $files['width'] = !empty($runtimeConfigs[$this->screen]['upscale']['min'][0]) ? $runtimeConfigs[$this->screen]['upscale']['min'][0] : $this->width;
        $files['height'] = !empty($runtimeConfigs[$this->screen]['upscale']['min'][1]) ? $runtimeConfigs[$this->screen]['upscale']['min'][1] : $this->height;
        $files['lazyFileSvg'] = 'data:image/svg+xml,%3Csvg width="'.$files['width'].'" height="'.$files['height'].'" xmlns="http://www.w3.org/2000/svg"%3E%3Crect x="0" y="0" width="'.$files['width'].'" height="'.$files['height'].'" fill="none"/%3E%3C/svg%3E';

        $this->files = (object) $files;
    }

    /**
     * To set WebLink.
     */
    private function setWebLink(array $options = []): void
    {
        $lazyLoad = $options['lazyLoad'] ?? true;
        if (!$lazyLoad) {
            $linkProvider = $this->coreLocator->request()->attributes->get('_links', new GenericLinkProvider());
            foreach ($this->files as $screen => $path) {
                if (str_contains($screen, $this->screen)) {
                    $this->coreLocator->request()->attributes->set('_links', $linkProvider->withLink(
                        (new Link('preload', $path))->withAttribute('as', 'image')
                    ));
                }
            }
        }
    }

    /**
     * To generate webp.
     */
    private function generateWebp(string $file, string $filter, string $screen): ?string
    {
        $screenExtension = str_replace('Retina', '', $screen);
        $dirname = str_replace([DIRECTORY_SEPARATOR, '.webp', $this->coreLocator->schemeAndHttpHost()], ['/', '', ''], $this->coreLocator->formatDirname($file));
        $dirname = $this->coreLocator->formatDirname($this->coreLocator->publicDir().$dirname);
        $img = $this->screensFilesExtensions[$screenExtension] === 'png' ? @imagecreatefrompng($dirname) : @imagecreatefromjpeg($dirname);

        if (!$img instanceof \GdImage) return null;

        $w = imagesx($img); $h = imagesy($img);
        $out = imagecreatetruecolor($w, $h);
        imagealphablending($out, false); imagesavealpha($out, true);
        $trans = imagecolorallocatealpha($out, 0, 0, 0, 127);
        imagefilledrectangle($out, 0, 0, $w, $h, $trans);
        imagecopy($out, $img, 0, 0, 0, 0, $w, $h);

        $canWebp = function_exists('imagewebp');

        [$fn, $quality, $dstPath] = match (true) {
            $canWebp => ['imagewebp', 85, preg_replace('/\.(png|jpe?g|avif)$/i', '.webp', $dirname)],
            $this->extension === 'png' => ['imagepng', 6, preg_replace('/\.(jpe?g|webp|avif)$/i', '.png', $dirname)],
            default => ['imagejpeg', 85, preg_replace('/\.(png|webp|avif)$/i', '.jpg', $dirname)],
        };

        if ($filter === 'media1') {
            for ($i = 0; $i < 20; $i++) { imagefilter($out, IMG_FILTER_GAUSSIAN_BLUR); imagefilter($out, IMG_FILTER_SMOOTH, 10); }
            $fn($out, $dstPath, $fn === 'imagepng' ? 0 : 50);
        } else {
            $fn($out, $dstPath, $fn === 'imagepng' ? 6 : $quality);
        }

        imagedestroy($out);
        imagedestroy($img);

        return str_replace([$this->coreLocator->publicDir(), DIRECTORY_SEPARATOR], ['', '/'] , $dstPath);
    }

    /**
     * To reset values.
     */
    private function resetValues(): void
    {
        $asBool = ['webpSupport', 'isImage', 'isPlaceHolder', 'generateThumb'];
        $reflectionObject = new \ReflectionObject($this);
        $properties = $reflectionObject->getProperties();
        foreach ($properties as $property) {
            if (!$property->isReadOnly() && 'screen' !== $property->getName()) {
                $method = $property->getName();
                $this->$method = in_array($property->getName(), $asBool) ? false : null;
            }
        }
    }

    /**
     * Get file Bytes.
     */
    private function setFormatBytes(?int $bytes = null, int $precision = 2): void
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $this->formatBytes = number_format($bytes / pow(1024, $power), $precision, '.', ',').' '.$units[$power];
    }

    /**
     * Get svg sizes.
     */
    private function svgSizes(?int $width, ?int $height): object
    {
        if ((new Filesystem())->exists($this->dirname)) {
            $svgWidth = null;
            $svgHeight = null;
            $svg = file_get_contents($this->dirname);
            preg_match('/viewBox="([^"]*)"/', $svg, $matches);
            $viewBox = !empty($matches[0]) && str_contains($matches[0], 'viewBox') && !empty($matches[1]) ? $matches[1] : null;
            if ($viewBox) {
                $matches = explode(' ', $viewBox);
                $svgWidth = !empty($matches[2]) && intval($matches[2]) > 0 ? intval($matches[2]) : null;
                $svgHeight = !empty($matches[3]) && intval($matches[3]) > 0 ? intval($matches[3]) : null;
            }
            if (!$svgWidth && !$svgHeight) {
                preg_match('/width="([^"]*)"/', $svg, $matches);
                $svgWidth = !empty($matches[1]) && intval($matches[1]) > 0 ? intval($matches[1]) : null;
                preg_match('/height="([^"]*)"/', $svg, $matches);
                $svgHeight = !empty($matches[1]) && intval($matches[1]) > 0 ? intval($matches[1]) : null;
            }
            if ($svgWidth && $svgHeight) {
                $width = !$width && $height ? (int) ceil(($svgWidth * $height) / $svgHeight) : (int) ceil($svgWidth);
                if (!$height) {
                    $height = $width ? (int) ceil(($svgHeight * $width) / $svgWidth) : (int) ceil($svgHeight);
                }
            }
        }

        return (object) [
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * Get files cache.
     */
    private function filesCache(): void
    {
        $filesystem = new Filesystem();
        $cacheDirname = $this->coreLocator->formatDirname($this->coreLocator->publicDir().'/thumbnails/cache/').'thumbs.cache.json';
        $this->filesCache = $filesystem->exists($cacheDirname) ? (array) json_decode(file_get_contents($cacheDirname)) : [];
    }

    /**
     * Get file cache path.
     */
    private function fileCache(string $path, array $runtimeConfig, string $filter): ?string
    {
        $matches = explode('/', $path);
        $filename = $runtimeConfig['thumbnail']['size'][0].'x'.$runtimeConfig['thumbnail']['size'][1].'-'.$filter.'-'.end($matches);

        return $this->filesCache[$filename] ?? null;
    }

    /**
     * Set file cache name.
     */
    private function setFileCache(string $path, array $runtimeConfig, string $thumbPath, string $filter): void
    {
        $matches = explode('/', $path);
        $filename = $runtimeConfig['thumbnail']['size'][0].'x'.$runtimeConfig['thumbnail']['size'][1].'-'.$filter.'-'.end($matches);

        if (!isset($this->filesCache[$filename]) && empty($options['noCache'])) {
            $cacheDirname = $this->coreLocator->formatDirname($this->coreLocator->publicDir().'/thumbnails/cache/');
            $filesystem = new Filesystem();
            if (!$filesystem->exists($cacheDirname)) {
                $filesystem->mkdir($cacheDirname);
            }
            $cacheDirname = $cacheDirname.'thumbs.cache.json';
            $this->filesCache[$filename] = $thumbPath;
            $fp = fopen($cacheDirname, 'w');
            fwrite($fp, json_encode($this->filesCache, JSON_PRETTY_PRINT));
            fclose($fp);
        }
    }
}
