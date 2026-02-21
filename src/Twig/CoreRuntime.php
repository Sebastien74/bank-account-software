<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\CoreLocatorInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * CoreRuntime
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
class CoreRuntime implements RuntimeExtensionInterface
{
    private array $cache = [];

    /**
     * CoreRuntime constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Get environment variable.
     */
    public function getEnv(string $name = null): bool|string
    {
        if (!empty($this->cache['env'][$name])) {
            return $this->cache['env'][$name];
        }

        $this->cache['env'][$name] = $name && !empty($_ENV[$name]) ? $_ENV[$name] : false;

        return $this->cache['env'][$name];
    }

    /**
     * Get route arguments.
     */
    public function routeArgs(?string $route = null, mixed $entity = null, array $parameters = []): array
    {
        return $this->coreLocator->routeArgs($route, $entity, $parameters);
    }

    /**
     * Get current request.
     */
    public function masterRequest(): ?Request
    {
        return $this->coreLocator->request();
    }

    /**
     * Get Cookies.
     */
    public function cookies(): array
    {
        $cookies = [];
        $cookiesNames = ['axeptio_cookies'];
        foreach ($cookiesNames as $name) {
            $cookiesRequest = $this->coreLocator->request()->cookies->get($name);
            $serializer = new Serializer([new GetSetMethodNormalizer()], ['json' => new JsonEncoder()]);
            if (!empty($cookiesRequest)) {
                $cookiesRequest = $serializer->decode($cookiesRequest, 'json');
                foreach ($cookiesRequest as $slug => $cookie) {
                    if (!empty($cookie['slug'])) {
                        $cookies[$cookie['slug']] = $cookie['status'];
                    } else {
                        $cookies[$slug] = $cookie;
                    }
                }
            }
        }

        return $cookies;
    }

    /**
     * Get entity value.
     */
    public function entityValue(mixed $object = null, ?string $property = null): mixed
    {
        $value = null;

        if (is_object($object) && $property) {
            $getMethod = 'get'.ucfirst($property);
            $isMethod = 'is'.ucfirst($property);
            $value = method_exists($object, $getMethod) ? $object->$getMethod()
                : (method_exists($object, $isMethod) ? $object->$isMethod() : (property_exists($object, $property) ? $object->$property : $object));
        }

        return $value;
    }

    /**
     * Check if is Instance of.
     */
    public function instanceof(mixed $var, string $instance): bool
    {
        return $var && $var instanceof $instance;
    }

    /**
     * Get Class name.
     */
    public function getClass(mixed $class): ?string
    {
        return $class ? get_class($class) : null;
    }

    /**
     * Check if is an object.
     */
    public function isObject(mixed $var): bool
    {
        return $var && is_object($var);
    }

    /**
     * Check if is boolean.
     */
    public function isBool(mixed $var): bool
    {
        return is_bool($var);
    }

    /**
     * Check if is an email.
     */
    public function isEmail(mixed $var): bool
    {
        return is_string($var) && filter_var($var, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Check if is DateTime.
     */
    public function isDateTime(mixed $var = null): bool
    {
        return $var instanceof \DateTime || $var instanceof \DateTimeImmutable;
    }

    /**
     * Count entity collection by property.
     */
    public function countCollection(mixed $entity = null, ?string $property = null): ?int
    {
        $getter = 'get'.ucfirst($property);
        if ($entity && is_object($entity) && method_exists($entity, $getter) && is_iterable($entity->$getter())) {
            return count($entity->$getter());
        }
        return null;
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

    /**
     * Remove text between.
     */
    public function removeBetween(string $string, array $tags): ?string
    {
        if (empty($tags[1])) {
            return preg_replace('/<\s*'.$tags[0].'.+?<\s*\/\s*'.$tags[0].'.*?>/si', ' ', $string);
        }
        return preg_replace('/\\'.$tags[0].'([^()]*+|(?R))*\\'.$tags[1].'/', '', $string);
    }

    /**
     * To convert html.
     */
    public function htmlEntities(?string $string = null, bool $stripTag = true): ?string
    {
        if (!$string) {
            return null;
        }

        $string = trim(html_entity_decode(mb_convert_encoding($string, 'UTF-8'), ENT_QUOTES, 'UTF-8'));
        $string = preg_replace('/\\s+/', ' ', $string);

        if ($stripTag) {
            $string = strip_tags($string);
            $string = preg_replace('/(?=[^\n\r\t])\p{Cc}/u', '', $string);
            $string = preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F\x00-\x1F]/', '', $string);
            $string = str_replace("\u{FEFF}", '', $string);
        }

        return $string;
    }

    /**
     * To decode json.
     */
    public function jsonPretty(?string $json): string
    {
        if ($json === null) {
            return '';
        }

        $json = trim($json);
        if ($json === '') {
            return '';
        }

        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return (string) json_encode(
                $decoded,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );
        } catch (\JsonException) {
            // Text mode
        }

        $out = preg_replace('/\s*:\s*/u', ":\n", $json) ?? $json;
        $out = str_replace("http:\n//", "http://", $out);

        return str_replace("https:\n//", "https://", $out);
    }
}
