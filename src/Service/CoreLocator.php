<?php

declare(strict_types=1);

namespace App\Service;

use DateInvalidOperationException;
use DateMalformedStringException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CoreLocator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => CoreLocator::class, 'key' => 'core_locator'],
])]
class CoreLocator implements CoreLocatorInterface
{
    private const array ALLOWED_IPS = ['::1', '127.0.0.1', 'fe80::1', '195.135.16.88', '176.135.112.19'];

    /**
     * CoreLocator constructor.
     */
    public function __construct(
        private readonly LastRouteService $lastRouteLocator,
        private readonly HttpFoundation\RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly RouterInterface $router,
        private readonly EntityManagerInterface $entityManager,
        private readonly InterfaceHelperInterface $interfaceHelper,
        private readonly string $projectDir,
        private readonly string $publicDir,
        private readonly string $logDir,
        private readonly string $cacheDir,
        private readonly bool $isDebug,
    ) {
    }

    /**
     * To get a company name.
     */
    public function companyName(): ?string
    {
        return 'Bank Account Software';
    }

    /**
     * To check IP.
     */
    public function checkIP(): bool
    {
        $allowedIps = array_unique(array_merge(self::ALLOWED_IPS));

        return (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && in_array($_SERVER['HTTP_X_FORWARDED_FOR'], $allowedIps, true))
            || (isset($_SERVER['HTTP_X_REAL_IP']) && in_array($_SERVER['HTTP_X_REAL_IP'], $allowedIps, true))
            || in_array(@$_SERVER['REMOTE_ADDR'], $allowedIps, true);
    }

    /**
     * To get RequestStack.
     */
    public function requestStack(): HttpFoundation\RequestStack
    {
        return $this->requestStack;
    }

    /**
     * To get Request.
     */
    public function request(): ?HttpFoundation\Request
    {
        return $this->requestStack->getMainRequest();
    }

    /**
     * To get schemeAndHttpHost.
     */
    public function schemeAndHttpHost(): ?string
    {
        return $this->request() ? $this->request()->getSchemeAndHttpHost() : null;
    }

    /**
     * To get locale.
     */
    public function locale(): ?string
    {
        return $this->request() ? $this->request()->getLocale() : 'fr';
    }

    /**
     * To check if the url is in admin render.
     */
    public function inAdmin(): bool
    {
        $uri = $this->request() instanceof HttpFoundation\Request ? $this->request()->getUri() : false;
        return $uri && preg_match('/\/back-'.$_ENV['SECURITY_TOKEN'].'/', $uri);
    }


    /**
     * To get entity interface.
     */
    public function entityInterface(mixed $entity, ?string $field = null): mixed
    {
        return $this->interfaceHelper->generate($entity, $field);
    }

    /**
     * To get RouterInterface.
     */
    public function router(): RouterInterface
    {
        return $this->router;
    }

    /**
     * To get LastRouteService.
     */
    public function lastRoute(): LastRouteService
    {
        return $this->lastRouteLocator;
    }

    /**
     * To get route args to generate route.
     *
     * @throws NonUniqueResultException|ContainerExceptionInterface
     */
    public function routeArgs(?string $route = null, mixed $entity = null, array $parameters = []): array
    {
        $haveParams = false;
        $entity = $entity && property_exists($entity, 'entity') ? $entity->entity : $entity;
        $currentRouteName = !empty($parameters['current_route']) ? $parameters['current_route'] : null;
        if ($currentRouteName) {
            unset($parameters['current_route']);
        }

        if ($route) {
            $routeInfos = $this->router()->getRouteCollection()->get($route);
            if ($routeInfos) {
                preg_match_all('/\{([^}]*)}/', $routeInfos->getPath(), $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $match) {
                        if (empty($parameters[$match])) {
                            $haveParams = true;
                            if ($this->request()->attributes->get($match) && is_numeric($this->request()->attributes->get($match))) {
                                $parameters[$match] = intval($this->request()->attributes->get($match));
                            } elseif ($entity && is_object($entity) && method_exists($entity, 'getId')) {
                                $interface = $this->entityInterface($entity);
                                if (!empty($interface['name']) && $match === $interface['name']) {
                                    $parameters[$match] = $entity->getId();
                                }
                                if ($match === $interface['masterField'] && method_exists($entity, $interface['masterFieldGetter']) && $entity->$interface['masterFieldGetter']()) {
                                    $parameters[$match] = $entity->$interface['masterFieldGetter']()->getId();
                                }
                            }
                        }
                    }
                }
            }
        }

        $currentRoutes = ['back_history_corrected', 'api_front_domain_check'];
        $currentRoute = $this->request()->getSession()->get('this_route');
        $previousRoute = in_array($currentRouteName, $currentRoutes) ? $currentRoute : $this->request()->getSession()->get('last_route');
        if ($route && $previousRoute && $previousRoute->name === $route && !str_contains($route, '_edit')) {
            $parameters = array_merge($parameters, $previousRoute->params);
        }

        return $haveParams && empty($parameters) ? [] : $parameters;
    }

    /**
     * To log in messages.json
     *
     * @throws DateMalformedStringException|DateInvalidOperationException
     */
    public function jsonLog(string $text, string $type = 'critical', string $filename = 'critical'): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $logDir = $projectRoot . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'log';
        $logDir = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $logDir);
        $filepath = $logDir . DIRECTORY_SEPARATOR . $filename . '.json';

        // Create directory if it does not exist.
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $format = 'Y-m-d H:i:s';
        $tz = new \DateTimeZone('Europe/Paris');
        $now = new \DateTimeImmutable('now', $tz);
        $today = $now->format('Y-m-d');

        // Load existing messages (or empty array).
        $messages = file_exists($filepath)
            ? (json_decode(file_get_contents($filepath), true) ?: [])
            : [];

        $threshold = $now->sub(new \DateInterval('P15D'));

        $filteredMessages = [];
        $duplicateForToday = false;

        // Clean old entries and check duplicate for today.
        foreach ($messages as $date => $msg) {
            $d = \DateTimeImmutable::createFromFormat($format, $date, $tz);

            // Skip invalid or too old entries.
            if (!$d || $d < $threshold) {
                continue;
            }

            // Check duplicate for same day, same type, same message.
            if (
                $d->format('Y-m-d') === $today
                && \is_array($msg)
                && ($msg['type'] ?? null) === $type
                && ($msg['message'] ?? null) === $text
            ) {
                $duplicateForToday = true;
            }

            // Keep valid entry.
            $filteredMessages[$date] = $msg;
        }

        // If duplicate found for today, do not add a new entry
        if ($duplicateForToday) {
            // You can still rewrite the file with the rotation applied.
            uksort($filteredMessages, static fn(string $a, string $b): int => strcmp($b, $a));
            file_put_contents($filepath, json_encode($filteredMessages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return;
        }

        // Add the current message.
        $filteredMessages[$now->format($format)] = [
            'type' => $type,
            'message' => $text,
        ];

        // Sort keys (dates) in descending order.
        uksort($filteredMessages, static fn(string $a, string $b): int => strcmp($b, $a));

        // Save JSON.
        file_put_contents($filepath, json_encode($filteredMessages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * To get TokenStorageInterface.
     */
    public function tokenStorage(): TokenStorageInterface
    {
        return $this->tokenStorage;
    }

    /**
     * To get AuthorizationCheckerInterface.
     */
    public function authorizationChecker(): AuthorizationCheckerInterface
    {
        return $this->authorizationChecker;
    }

    /**
     * To get the current User.
     */
    public function user(): ?UserInterface
    {
        if (!empty($this->tokenStorage->getToken())) {
            return $this->tokenStorage->getToken()->getUser();
        }

        return null;
    }

    /**
     * Is granted user.
     */
    public function granted(string $roleName): bool
    {
        $user = $this->user();
        return $user instanceof UserInterface && in_array($roleName, $user->getRoles());
    }

    /**
     * To get TranslatorInterface.
     */
    public function translator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * To get EntityManagerInterface.
     */
    public function entityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * To get EntityManagerInterface.
     */
    public function em(): EntityManagerInterface
    {
        return $this->entityManager();
    }

    /**
     * To get projectDir.
     */
    public function projectDir(): string
    {
        return $this->formatDirname($this->projectDir);
    }

    /**
     * To get publicDir.
     */
    public function publicDir(): string
    {
        return $this->formatDirname($this->publicDir);
    }

    /**
     * To get logDir.
     */
    public function logDir(): string
    {
        return $this->formatDirname($this->logDir);
    }

    /**
     * To get cacheDir.
     */
    public function cacheDir(): string
    {
        return $this->formatDirname($this->cacheDir);
    }

    /**
     * To get isDebug.
     */
    public function isDebug(): bool
    {
        return $this->isDebug;
    }

    /**
     * To set format dirname.
     */
    public function formatDirname(?string $dirname): ?string
    {
        return $dirname ? str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname) : null;
    }
}
