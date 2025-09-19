<?php

declare(strict_types=1);

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CoreLocatorInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface CoreLocatorInterface
{
    public function checkIP(): bool;

    public function requestStack(): HttpFoundation\RequestStack;

    public function request(): ?HttpFoundation\Request;

    public function schemeAndHttpHost(): ?string;

    public function locale(): ?string;

    public function router(): RouterInterface;

    public function lastRoute(): LastRouteService;

    public function routeArgs(?string $route = null, mixed $entity = null, array $parameters = []): array;

    public function tokenStorage(): TokenStorageInterface;

    public function authorizationChecker(): AuthorizationCheckerInterface;

    public function user(): ?UserInterface;

    public function translator(): TranslatorInterface;

    public function em(): EntityManagerInterface;

    public function projectDir(): string;

    public function logDir(): string;

    public function cacheDir(): string;

    public function isDebug(): bool;
}
