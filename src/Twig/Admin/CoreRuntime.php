<?php

declare(strict_types=1);

namespace App\Twig\Admin;

use App\Entity\Core\Log;
use App\Entity\Security\Group;
use App\Entity\Security\User;
use App\Service\Interface\CoreLocatorInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Kernel;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * CoreRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CoreRuntime implements RuntimeExtensionInterface
{
    /**
     * CoreRuntime constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * To get Symfony version.
     */
    public function symfonyVersion(): string
    {
        $version = Kernel::VERSION;
        $matches = explode('.', $version);
        $minVersion = strlen('.'.end($matches));

        return substr($version, 0, -$minVersion);
    }

    /**
     * To get PHP version.
     */
    public function phpversion(): string
    {
        $version = phpversion();
        $matches = explode('.', $version);
        $minVersion = strlen('.'.end($matches));

        return substr($version, 0, -$minVersion);
    }

    /**
     * To check if entity is allowed to show in index for current User.
     *
     * @throws InvalidArgumentException
     */
    public function indexAllowed(mixed $entity, bool $isInternal): bool
    {
        if ($entity instanceof Group) {
            $isInternalGroup = false;
            foreach ($entity->getRoles() as $role) {
                if ('ROLE_INTERNAL' === $role->getName()) {
                    $isInternalGroup = true;
                    break;
                }
            }
            if ($isInternalGroup && !$isInternal) {
                return false;
            }
        } elseif ($entity instanceof User) {
            $isInternalUser = false;
            foreach ($entity->getRoles() as $role) {
                if ('ROLE_INTERNAL' === $role) {
                    $isInternalUser = true;
                    break;
                }
            }
            if ($isInternalUser && !$isInternal) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get season icon.
     *
     * @throws \Exception
     */
    public function seasonIcon(): ?string
    {
        $currentDate = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $year = $currentDate->format('Y');
        if ($currentDate >= new \DateTime($year.'-09-22 00:00:00', new \DateTimeZone('Europe/Paris'))
            && $currentDate <= new \DateTime($year.'-12-21 23:59:59', new \DateTimeZone('Europe/Paris'))) {
            return 'admin umbrella-color';
        }
        if ($currentDate >= new \DateTime($year.'-12-22 00:00:00', new \DateTimeZone('Europe/Paris'))
            && $currentDate <= new \DateTime((intval($year) + 1).'-03-19 23:59:59', new \DateTimeZone('Europe/Paris'))) {
            return 'admin tree-color';
        }
        if ($currentDate >= new \DateTime((intval($year) - 1).'-12-22 00:00:00', new \DateTimeZone('Europe/Paris'))
            && $currentDate <= new \DateTime($year.'-03-19 23:59:59', new \DateTimeZone('Europe/Paris'))) {
            return 'admin tree-color';
        }
        if ($currentDate >= new \DateTime($year.'-03-20 00:00:00', new \DateTimeZone('Europe/Paris'))
            && $currentDate <= new \DateTime($year.'-06-19 23:59:59', new \DateTimeZone('Europe/Paris'))) {
            return 'admin spring-color';
        }
        if ($currentDate >= new \DateTime($year.'-06-20 00:00:00', new \DateTimeZone('Europe/Paris'))
            && $currentDate <= new \DateTime($year.'-09-21 23:59:59', new \DateTimeZone('Europe/Paris'))) {
            return 'admin sun-color';
        }

        return 'fad globe';
    }

    /**
     * Get log alert.
     */
    public function logAlert(): bool
    {
        $lastLog = $this->coreLocator->em()->getRepository(Log::class)->findUnread();

        return !empty($lastLog);
    }

    /**
     * Init entity property if not existing.
     */
    public function methodInit(mixed $entity, string $property): string
    {
        $getter = 'get'.ucfirst($property);

        return method_exists($entity, $getter) ? $property : 'id';
    }

    /**
     * Check button display status.
     */
    public function buttonChecker(string $route, mixed $entity, array $interface = []): mixed
    {
        if (isset($interface['buttonsChecker'][$route])) {
            $properties = explode('.', $interface['buttonsChecker'][$route]);
            $display = true;
            foreach ($properties as $property) {
                $getter = 'get'.ucfirst($property);
                if (is_object($entity) && method_exists($entity, $getter)) {
                    $display = $entity->$getter();
                    $entity = $entity->$getter();
                }
            }

            return $display;
        }

        return true;
    }

    /**
     * Check button display by User role.
     */
    public function buttonRoleChecker(string $route, array $interface = []): bool
    {
        if (isset($interface['rolesChecker'][$route])) {
            return $this->coreLocator->authorizationChecker()->isGranted($interface['rolesChecker'][$route]);
        }

        return true;
    }
}
