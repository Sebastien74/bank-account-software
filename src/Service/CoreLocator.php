<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CoreLocator.
 *
 * To load base Services
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => CoreLocator::class, 'key' => 'core_locator'],
])]
class CoreLocator implements CoreLocatorInterface
{
    private const array ALLOWED_IPS = ['::1', '127.0.0.1', 'fe80::1'];
    private array $cache = [];

    /**
     * CoreLocator constructor.
     */
    public function __construct(
        #[AutowireLocator(LastRouteService::class, indexAttribute: 'key')] protected ServiceLocator $lastRouteLocator,
        private readonly HttpFoundation\RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly RouterInterface $router,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $projectDir,
        private readonly string $logDir,
        private readonly string $cacheDir,
        private readonly bool $isDebug,
    ) {
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
     * To get RouterInterface.
     */
    public function router(): RouterInterface
    {
        return $this->router;
    }

    /**
     * To get LastRouteService.
     *
     * @throws ContainerExceptionInterface
     */
    public function lastRoute(): LastRouteService
    {
        return $this->lastRouteLocator->get('last_route_service');
    }

    /**
     * To get route args to generate route.
     *
     * @throws NonUniqueResultException|ContainerExceptionInterface
     */
    public function routeArgs(?string $route = null, mixed $entity = null, array $parameters = []): array
    {
        if ($route) {
            $routeInfos = $this->router()->getRouteCollection()->get($route);
            if ($routeInfos) {
                preg_match_all('/\{([^}]*)\}/', $routeInfos->getPath(), $matches);
                if (!empty($matches[1])) {
                    foreach ($matches[1] as $match) {
                        if (empty($parameters[$match])) {
                            if ($this->request()->get($match) && is_numeric($this->request()->get($match))) {
                                $parameters[$match] = intval($this->request()->get($match));
                            } elseif ($entity && is_object($entity) && method_exists($entity, 'getId')) {
                                $interface = $entity::getInterface();
                                $masterField = !empty($interface['masterField']) ? $interface['masterField'] : false;
                                $masterFieldGetter = $masterField ? 'get'.ucfirst($masterField) : false;
                                if (!empty($interface['name']) && $match === $interface['name']) {
                                    $parameters[$match] = $entity->getId();
                                }
                                if ($match === $masterField && method_exists($entity, $masterFieldGetter) && $entity->$masterFieldGetter()) {
                                    $parameters[$match] = $entity->$masterFieldGetter()->getId();
                                }
                            }
                        }
                    }
                }
            }
        }

        return $parameters;
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
     * To get current User.
     */
    public function user(): ?UserInterface
    {
        if (!empty($this->tokenStorage->getToken())) {
            return $this->tokenStorage->getToken()->getUser();
        }

        return null;
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
        return $this->projectDir;
    }

    /**
     * To get logDir.
     */
    public function logDir(): string
    {
        return $this->logDir;
    }

    /**
     * To get cacheDir.
     */
    public function cacheDir(): string
    {
        return $this->cacheDir;
    }

    /**
     * To get isDebug.
     */
    public function isDebug(): bool
    {
        return $this->isDebug;
    }
}
