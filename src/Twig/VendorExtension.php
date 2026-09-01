<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * VendorExtension.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class VendorExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('getEnv', [CoreRuntime::class, 'getEnv']),
            new TwigFilter('routeArgs', [CoreRuntime::class, 'routeArgs']),
            new TwigFilter('masterRequest', [CoreRuntime::class, 'masterRequest']),
            new TwigFilter('cookies', [CoreRuntime::class, 'cookies']),
            new TwigFilter('entityValue', [CoreRuntime::class, 'entityValue']),
            new TwigFilter('instanceof', [CoreRuntime::class, 'instanceof']),
            new TwigFilter('getClass', [CoreRuntime::class, 'getClass']),
            new TwigFilter('isObject', [CoreRuntime::class, 'isObject']),
            new TwigFilter('isBool', [CoreRuntime::class, 'isBool']),
            new TwigFilter('isEmail', [CoreRuntime::class, 'isEmail']),
            new TwigFilter('isDateTime', [CoreRuntime::class, 'isDateTime']),
            new TwigFilter('countCollection', [CoreRuntime::class, 'countCollection']),
            new TwigFilter('formatDate', [CoreRuntime::class, 'formatDate']),
            new TwigFilter('removeBetween', [CoreRuntime::class, 'removeBetween']),
            new TwigFilter('htmlEntities', [CoreRuntime::class, 'htmlEntities']),
            new TwigFilter('jsonPretty', [CoreRuntime::class, 'jsonPretty']),
            new TwigFilter('iframe', [GdprRuntime::class, 'iframe']),
            new TwigFilter('icon', [IconRuntime::class, 'icon'], ['is_safe' => ['html']]),
            new TwigFilter('csp_nonce', [NonceRuntime::class, 'getNonce']),
            new TwigFilter('img', [MediaRuntime::class, 'img'], ['is_safe' => ['html']]),
            new TwigFilter('granted', [SecurityRuntime::class, 'granted']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getEnv', [CoreRuntime::class, 'getEnv']),
            new TwigFunction('routeArgs', [CoreRuntime::class, 'routeArgs']),
            new TwigFunction('masterRequest', [CoreRuntime::class, 'masterRequest']),
            new TwigFunction('cookies', [CoreRuntime::class, 'cookies']),
            new TwigFunction('entityValue', [CoreRuntime::class, 'entityValue']),
            new TwigFunction('instanceof', [CoreRuntime::class, 'instanceof']),
            new TwigFunction('getClass', [CoreRuntime::class, 'getClass']),
            new TwigFunction('isObject', [CoreRuntime::class, 'isObject']),
            new TwigFunction('isBool', [CoreRuntime::class, 'isBool']),
            new TwigFunction('isEmail', [CoreRuntime::class, 'isEmail']),
            new TwigFunction('isDateTime', [CoreRuntime::class, 'isDateTime']),
            new TwigFunction('countCollection', [CoreRuntime::class, 'countCollection']),
            new TwigFunction('formatDate', [CoreRuntime::class, 'formatDate']),
            new TwigFunction('removeBetween', [CoreRuntime::class, 'removeBetween']),
            new TwigFunction('htmlEntities', [CoreRuntime::class, 'htmlEntities']),
            new TwigFunction('jsonPretty', [CoreRuntime::class, 'jsonPretty']),
            new TwigFunction('iframe', [GdprRuntime::class, 'iframe']),
            new TwigFunction('icon', [IconRuntime::class, 'icon'], ['is_safe' => ['html']]),
            new TwigFunction('ux_icon_name', [IconRuntime::class, 'uxIconName']),
            new TwigFunction('csp_nonce', [NonceRuntime::class, 'getNonce']),
            new TwigFunction('img', [MediaRuntime::class, 'img'], ['is_safe' => ['html']]),
            new TwigFunction('granted', [SecurityRuntime::class, 'granted']),
        ];
    }
}
