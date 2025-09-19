<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * LastRouteService.
 *
 * To register last route in Session.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => LastRouteService::class, 'key' => 'last_route_service'],
])]
class LastRouteService
{
    /**
     * To execute service.
     */
    public function execute(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $uri = $request->getUri();
        $routeName = $request->get('_route');
        $session = $request->getSession();

        $routeParams = $request->get('_route_params');
        if ('_' == $routeName[0]) {
            return;
        }

        $routeData = (object) ['name' => $routeName, 'params' => $routeParams];
        $thisRoute = $session->get('this_route', []);
        if ($thisRoute == $routeData) {
            return;
        }

        $session->set('last_uri', $uri);
        $session->set('last_route', $thisRoute);
        $session->set('this_route', $routeData);
        $session->set('previous_secure_url', $uri);
    }
}
