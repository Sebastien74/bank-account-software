<?php

declare(strict_types=1);

namespace App\Twig;

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
readonly class MediaRuntime implements RuntimeExtensionInterface
{
    /**
     * ThumbnailRuntime constructor.
     */
    public function __construct(private Environment $templating)
    {

    }

    /**
     * To render image.
     *
     * @throws LoaderError|RuntimeError|SyntaxError
     */
    public function img(string $filename, ?string $packageName = null, array $options = []): void
    {
        echo $this->templating->render('core/image.html.twig', $options);
    }
}