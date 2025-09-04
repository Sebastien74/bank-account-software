<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Security\User;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\QueryException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Http\Event\SwitchUserEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * SwitchUserSubscriber.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SwitchUserSubscriber implements EventSubscriberInterface
{
    /**
     * On switch User Event.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    public function onSwitchUser(SwitchUserEvent $event): void
    {
        /** @var User $user */
        $user = $event->getTargetUser();
        $request = $event->getRequest();

        if ($request->hasSession() && ($session = $request->getSession())) {
            $session->set('_locale', $user->getLocale());
            $inAdmin = preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $request->getUri());
            $redirection = $inAdmin ? $request->getSchemeAndHttpHost().'/admin-'.$_ENV['SECURITY_TOKEN'].'/dashboard' : $request->getUri();
            $response = new RedirectResponse($redirection);
            if ('_exit' === $request->get('_switch_user')) {
                $response->headers->setCookie(Cookie::create('USER_IMPERSONATOR', '0'));
                if (!$inAdmin) {
                    $response->headers->setCookie(Cookie::create('IS_IMPERSONATOR_FRONT', '0'));
                }
            } else {
                $method = $user instanceof User ? 'get'.ucfirst($_ENV['SECURITY_ADMIN_LOGIN_TYPE'])
                    : 'get'.ucfirst($_ENV['SECURITY_FRONT_LOGIN_TYPE']);
                $response->headers->setCookie(Cookie::create('USER_IMPERSONATOR', $user->$method()));
                $response->headers->setCookie(Cookie::create('IS_IMPERSONATOR', '1'));
            }
            $response->send();
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            SecurityEvents::SWITCH_USER => 'onSwitchUser',
        ];
    }
}
