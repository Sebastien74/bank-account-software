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
    public function companyName(): ?string;

    public function checkIP(): bool;

    public function requestStack(): HttpFoundation\RequestStack;

    public function request(): ?HttpFoundation\Request;

    public function schemeAndHttpHost(): ?string;

    public function locale(): ?string;

    public function inAdmin(): bool;

    public function entityInterface(mixed $entity, ?string $field = null): mixed;

    public function router(): RouterInterface;

    public function lastRoute(): LastRouteService;

    public function routeArgs(?string $route = null, mixed $entity = null, array $parameters = []): array;

    public function jsonLog(string $text, string $type = 'critical', string $filename = 'critical');

    public function tokenStorage(): TokenStorageInterface;

    public function authorizationChecker(): AuthorizationCheckerInterface;

    public function user(): ?UserInterface;
    
    public function granted(string $roleName): bool;

    public function translator(): TranslatorInterface;

    public function em(): EntityManagerInterface;

    public function projectDir(): string;

    public function publicDir(): string;

    public function logDir(): string;

    public function cacheDir(): string;

    public function isDebug(): bool;

    public function formatDirname(?string $dirname): ?string;
}
