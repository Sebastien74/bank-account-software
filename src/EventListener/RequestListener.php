<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Service\CoreLocatorInterface;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * RequestListener.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RequestListener
{
    private RequestEvent $event;
    private ?Request $request = null;
    private ?string $uri = null;

    /**
     * RequestListener constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * onKernelRequest.
     *
     * @throws Exception
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $this->request = $event->getRequest();
        if (!$event->isMainRequest() || !$this->isMainRequest()) {
            return;
        }

        $this->event = $event;
        $this->uri = $this->request->getUri();
        $asIndexFile = 'index.php' === str_replace('/', '', $this->request->getRequestUri());

        if ($asIndexFile) {
            $this->event->setResponse(new RedirectResponse($this->request->getSchemeAndHttpHost(), 301));
        }

        $isLogin = str_contains($this->uri, '/secure/user');
        $isFront = !str_contains($this->uri, '/admin-'.$_ENV['SECURITY_TOKEN'].'/') && !$isLogin || str_contains($this->uri, '/preview/');
        if ($isFront) {
            $this->checkDisabledUris();
        }

        $this->coreLocator->lastRoute()->execute($event);

        if ('login' === trim($this->request->getRequestUri(), '/') && $this->coreLocator->checkIP()) {
            $this->event->setResponse(new RedirectResponse($this->coreLocator->router()->generate('security_login')));
        } elseif (!$isLogin && !$this->coreLocator->user() instanceof UserInterface) {
            $this->event->setResponse(new RedirectResponse($this->coreLocator->router()->generate('security_login')));
        }
    }

    /**
     * Check if is mainRequest.
     */
    private function isMainRequest(): bool
    {
        $excludedRoutes = ['_wdt', '_fragment', '_profiler'];
        if (in_array($this->request->attributes->get('_route'), $excludedRoutes)
            || str_contains($this->request->getUri(), 'front/crypt')
            || str_contains($this->request->getUri(), '_wdt')
            || str_contains($this->request->getUri(), '_profiler')
            || str_contains($this->request->getUri(), '_fragment') && str_contains($this->request->getUri(), '_hash')) {
            return false;
        }

        return true;
    }


    /**
     * Check if is disabled URI.
     */
    private function checkDisabledUris(): void
    {
        if ($this->uri) {
            $disabledPatterns = ['wordpress', 'wp-', 'autodiscover'];
            foreach ($disabledPatterns as $pattern) {
                if (str_contains($this->uri, $pattern)) {
                    $this->event->setResponse(new RedirectResponse($this->request->getSchemeAndHttpHost(), 301));
                }
            }
        }
    }
}
