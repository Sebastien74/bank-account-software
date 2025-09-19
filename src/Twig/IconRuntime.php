<?php

declare(strict_types=1);

namespace App\Twig;

use Symfony\Component\Filesystem\Filesystem;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * IconRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
readonly class IconRuntime implements RuntimeExtensionInterface
{
    /**
     * IconRuntime constructor.
     */
    public function __construct(private string $projectDir)
    {
    }

    /**
     * Get icon.
     */
    public function icon(
        string $icon,
        ?int $width = null,
        ?int $height = null,
        ?string $class = null,
        ?array $options = [],
        ?string $fill = null,
        bool $echo = true
    ): ?string {

        if (!$icon) {
            return null;
        }

        $options['class'] = !empty($options['class']) ? $options['class'] : $class;
        $options['width'] = !empty($options['width']) ? $options['width'] : $width;
        $options['height'] = !empty($options['height']) ? $options['height'] : $height;
        $options['fill'] = !empty($options['fill']) ? $options['fill'] : $fill;
        $options['id'] = !empty($options['id']) ? $options['id'] : '_'.uniqid();
        $options['title'] = !empty($options['title']) ? $options['title'] : $icon;

        $category = str_replace(['fab', 'fam', 'fad', 'fal', 'far', 'fas'], ['brands', 'main', 'duotone', 'light', 'regular', 'solid'], $icon);
        $matches = explode(' ', $category);
        $category = $matches[0];
        $filename = end($matches);
        $path = '/'.$category.'/'.$filename.'.svg';

        return $this->iconHtml($path, $options, $echo);
    }

    /**
     * Get icon content.
     */
    private function iconHtml(?string $iconPath = null, array $options = [], bool $echo = true): ?string
    {
        if (!$iconPath) {
            return '';
        }

        $iconPath = !str_contains($iconPath, $this->projectDir) ? str_replace(['medias/icons', 'medias\\icons'], '', $iconPath) : $iconPath;
        $dirname = !str_contains($iconPath, $this->projectDir) ? $this->projectDir.'/public/medias/icons/'.ltrim($iconPath, '/, \\') : $iconPath;
        $fileSystem = new Filesystem();
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        $matches = explode('.', $dirname);
        $extension = !empty($matches[1]) ? $matches[1] : null;
        $dirname = $extension ? $dirname : $dirname.'.svg';
        $dirname = !str_contains($dirname, '.svg') ? $dirname.'.svg' : $dirname;
        if (isset($options['decor']) && true === $options['decor']) {
            $options['aria-hidden'] = 'true';
        } else {
            $options['role'] = empty($options['role']) ? 'img' : $options['role'];
        }

        if ($fileSystem->exists($dirname)) {
            $svg = file_get_contents($dirname);
            $svg = preg_replace('/<\?(?!<!)[^\[>].*\?>/', '', $svg);
            $svg = preg_replace('/<!--\?(?!<!)[^\[>].*\?-->/', '', $svg);
            $svg = preg_replace('/<!(?!<!)[^\[>].*?>/', '', $svg);
            $attributes = ['id', 'width', 'title', 'height', 'fill', 'class', 'role', 'aria-hidden'];
            $regex = "/<\/?\w+((\s+(\w|\w[\w-]*\w)(\s*=\s*(?:\".*?\"|'.*?'|[^'\">\s]+))?)+\s*|\s*)\/?>/i";
            $svg = str_replace('\'', '"', $svg);
            foreach ($attributes as $attribute) {
                if (isset($options[$attribute])) {
                    preg_match($regex, $svg, $matchesSvg);
                    $svgElement = $matchesSvg[0];
                    preg_match('/'.$attribute.'="([^"]*)"/', $svgElement, $matches);
                    if (!empty($matches[0])) {
                        $newSvgElement = str_replace($matches[0], $attribute.'="'.$options[$attribute].'"', $svgElement);
                        $svg = str_replace($svgElement, $newSvgElement, $svg);
                    } else {
                        $svg = str_replace('<svg', '<svg '.$attribute.'="'.$options[$attribute].'"', $svg);
                    }
                }
            }
            if ($echo) {
                echo $svg;
            } else {
                return $svg;
            }
        }

        return null;
    }
}
