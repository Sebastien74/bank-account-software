<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * LoginListener.
 *
 * Listen User login event
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class LoginListener
{
    /**
     * LoginListener constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * onSecurityInteractiveLogin.
     *
     * @throws Exception
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if ($user instanceof User) {
            $user->setIsOnline(true);
            if (method_exists($user, 'setLastLogin')) {
                $user->setLastLogin(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            }
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        }
    }
}
