<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\CoreLocatorInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * GdprRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
readonly class GdprRuntime implements RuntimeExtensionInterface
{
    /**
     * GdprRuntime constructor.
     */
    public function __construct(
        private CoreLocatorInterface $coreLocator,
        private Environment          $twig,
    ) {
    }

    /**
     * Set iframe.
     *
     * @throws LoaderError|RuntimeError|SyntaxError
     */
    public function iframe(?string $iframeCode = null, array $options = []): void
    {
        $filesystem = new Filesystem();
        $arguments = array_merge($this->gdprArguments(), $options);
        $arguments['code'] = $imageCode = !empty($options['code']) ? $options['code'] : 'iframe';
        if ($iframeCode && str_contains($iframeCode, 'google.com/maps') && 'gmaps' !== $arguments['code']) {
            $arguments['code'] = $imageCode = 'gmaps';
        }
        if ($iframeCode && str_contains($iframeCode, 'elfsight') && 'apps-elfsight' !== $arguments['code']) {
            $arguments['code'] = $imageCode = 'elfsight';
        }
        $prototypeArguments = array_merge(['iframeCode' => $iframeCode], $arguments);
        $imgDirname = $this->coreLocator->formatDirname($this->coreLocator->projectDir().'/assets/medias/images/front/gdpr/'.$imageCode.'-gdpr.svg');
        if (!$filesystem->exists($imgDirname)) {
            $imageCode = 'iframe';
        }
        $prototypeArguments['image'] = 'build/front/images/gdpr/'.$imageCode.'-gdpr.svg';
        $arguments['prototype'] = $this->twig->render('front/gdpr/services/iframe-prototype.html.twig', $prototypeArguments);
        $arguments['prototype_placeholder'] = $this->twig->render('front/gdpr/services/iframe-prototype-placeholder.html.twig', $prototypeArguments);
        echo $this->twig->render('front/gdpr/services/iframe.html.twig', $arguments);
    }

    /**
     * To get base arguments.
     */
    public function gdprArguments(): array
    {
        $configuration = $this->coreLocator->configuration();
        $arguments = [];
        $arguments['axeptioId'] = $configuration->getAxeptioId();
        $arguments['axeptioExternal'] = $configuration->isAxeptioInGtm();
        $arguments['gdprActive'] = $arguments['axeptioId'] || $arguments['axeptioExternal'];

        return $arguments;
    }
}
