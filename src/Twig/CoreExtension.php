<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\CoreLocatorInterface;
use Exception;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * CoreExtension
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
readonly class CoreExtension implements RuntimeExtensionInterface
{
    public function __construct(private CoreLocatorInterface $coreLocator)
    {
    }

    public function routeArgs(?string $route = null, mixed $entity = null, array $parameters = []): array
    {
        return $this->coreLocator->routeArgs($route, $entity, $parameters);
    }

    /**
     * Get format Date by locale.
     *
     * @throws Exception
     */
    public function formatDate(?string $locale = null): object
    {
        $locale = !$locale ? $this->coreLocator->request()->getLocale() : $locale;
        $formatter = new \IntlDateFormatter($locale, \IntlDateFormatter::SHORT, \IntlDateFormatter::SHORT);
        $fullOrigin = $formatter->getPattern();
        $matches = explode(' ', $fullOrigin);

        $matchesFormat = explode('/', $matches[0]);
        if ('dd' === $matchesFormat[0]) {
            $formatter->setPattern('DD/MM/YYYY H:i:s');
        } else {
            $formatter->setPattern('YYYY/MM/DD g:i:s A');
        }

        $large = $formatter->getPattern();
        $matchesLarge = explode(' ', $large);
        $monthDay = !empty($matches[0]) ? rtrim(ltrim(str_replace('y', '', $matches[0]), '/'), '/') : null;
        $month = rtrim(ltrim(str_replace('d', '', $monthDay), '/'), '/');

        if ('dd' === $matchesFormat[0]) {
            $formatter->setPattern('dd/mm/yyyy');
        } else {
            $formatter->setPattern('yyyy/mm/dd');
        }
        $datepicker = $formatter->getPattern();

        return (object) [
            'dateTime' => $fullOrigin,
            'dateTimeLarge' => $large,
            'date' => !empty($matches[0]) ? $matches[0] : null,
            'dateLarge' => !empty($matchesLarge[0]) ? $matchesLarge[0] : null,
            'dayMonth' => strtolower($monthDay),
            'dayMonthLarge' => $monthDay,
            'month' => strtolower($month),
            'monthLarge' => $month,
            'time' => !empty($matches[0]) ? $matches[0] : null,
            'timeLarge' => !empty($matches[1]) ? $matches[1] : null,
            'inputDate' => str_replace(['dd', 'mm', 'yyyy'], ['d', 'm', 'Y'], $datepicker),
            'datepickerPHP' => str_replace('mm', 'MM', $datepicker),
            'datepickerJS' => $datepicker,
        ];
    }
}