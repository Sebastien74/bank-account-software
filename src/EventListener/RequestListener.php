<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\Core\Website;
use App\Model\Core\WebsiteModel;
use App\Security\Interface\UserCheckerInterface;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\PhpArrayAdapter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * RequestListener.
 *
 * Listen front events
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RequestListener
{
    private RequestEvent $event;
    private ?Request $request = null;
    private ?SessionInterface $session;
    private ?WebsiteModel $website = null;
    private ?string $uri = null;
    private ?string $routeName = null;

    /**
     * RequestListener constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly UserCheckerInterface $userChecker,
    ) {
    }

    /**
     * onKernelRequest.
     *
     * @throws NonUniqueResultException|Exception|InvalidArgumentException
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $this->request = $event->getRequest();
        $this->routeName = $this->request->attributes->get('_route');
        if (!$event->isMainRequest() || !$this->isMainRequest() || $this->isSubRequest()) {
            return;
        }

        $this->event = $event;
        $this->session = $this->request->getSession();
        $this->uri = $this->request->getUri();
        $asIndexFile = 'index.php' === str_replace('/', '', $this->request->getRequestUri());

        if ($asIndexFile) {
            $this->event->setResponse(new RedirectResponse($this->request->getSchemeAndHttpHost(), 301));
        }

        $isLogin = str_contains($this->uri, '/secure/user');
        $isFront = !str_contains($this->uri, '/admin-'.$_ENV['SECURITY_TOKEN'].'/') && !$isLogin || str_contains($this->uri, '/preview/');

        $this->website = $this->coreLocator->website();
        $this->coreLocator->lastRoute()->execute($event);
        $this->request->getSession()->remove('mainExceptionMessage');

        if (!$isLogin && !$this->coreLocator->user() instanceof UserInterface) {
            $this->event->setResponse(new RedirectResponse($this->coreLocator->router()->generate('security_login')));
        }

        if ($isFront) {
            $this->checkDisabledUris();
            $this->frontRequest();
        } elseif (!$isLogin) {
            $this->adminRequest();
        }

        $this->userChecker->execute($event, $this->website);
    }

    /**
     * Check if is subRequest.
     */
    private function isSubRequest(): bool
    {
        $routes = [
            'front_render_block',
            'front_encrypt',
            'front_decrypt',
            'front_webmaster_toolbox',
            'front_gdpr_scripts',
        ];

        if (in_array($this->routeName, $routes)) {
            return true;
        }

        return false;
    }

    /**
     * Check if is mainRequest.
     *
     * @throws InvalidArgumentException
     */
    private function isMainRequest(): bool
    {
        $excludedRoutes = ['_wdt', '_fragment'];
        if (in_array($this->routeName, $excludedRoutes)
            || str_contains($this->request->getUri(), '_wdt')
            || str_contains($this->request->getUri(), '_fragment') && str_contains($this->request->getUri(), '_hash')) {
            return false;
        }

        $filesystem = new Filesystem();
        $dirname = $this->coreLocator->cacheDir().'/routes.cache';
        $dirname = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dirname);
        if ($filesystem->exists($dirname)) {
            $cache = new PhpArrayAdapter($dirname, new FilesystemAdapter());
            $item = $cache->getItem('route.'.$this->routeName);
            if ($item->isHit() && !$item->get()['isMainRequest']) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if is disabled URI.
     */
    private function checkDisabledUris(): void
    {
        if ($this->uri) {
            $disabledPatterns = ['wordpress', 'wp-includes', 'wp-admin', 'autodiscover'];
            foreach ($disabledPatterns as $pattern) {
                if (str_contains($this->uri, $pattern)) {
                    $this->event->setResponse(new RedirectResponse($this->request->getSchemeAndHttpHost(), 301));
                }
            }
        }
    }

    /**
     * Check front Request.
     *
     * @throws NonUniqueResultException|InvalidArgumentException|MappingException
     */
    private function frontRequest(): void
    {
        if ('login' === trim($this->request->getRequestUri(), '/') && $this->coreLocator->checkIP($this->website)) {
            $this->event->setResponse(new RedirectResponse($this->coreLocator->router()->generate('security_login')));
        } else {
            $response = $this->coreLocator->redirectionService()->execute($this->request);
            if ($response['urlRedirection'] && str_contains($response['urlRedirection'], 'http')) {
                $this->event->setResponse(new RedirectResponse($response['urlRedirection'], 301));
            } elseif ($response['domainRedirection']) {
                $this->event->setResponse(new RedirectResponse($response['domainRedirection'], 301));
            } elseif ($response['urlRedirection']) {
                $this->event->setResponse(new RedirectResponse($response['urlRedirection'], 301));
            } elseif ($response['banRedirection']) {
                $this->event->setResponse(new RedirectResponse($response['banRedirection'], 302));
            }
            $this->website = $response['website'];
        }
    }

    /**
     * Check admin Request.
     *
     * @throws Exception
     */
    private function adminRequest(): void
    {
        $websiteRequest = $this->request->get('website');
        $repository = $this->coreLocator->em()->getRepository(Website::class);
        $website = is_numeric($websiteRequest) ? $repository->findByIdForAdmin(intval($websiteRequest)) : $repository->findDefault();

        if (!$website) {
            $website = $repository->findDefault();
            if ($website) {
                $this->event->setResponse(new RedirectResponse($this->coreLocator->router()->generate('admin_dashboard', ['website' => $website->id]), 302));
            }
        }

        if ($this->request->get('admin_dark_theme') || $this->request->get('admin_dark_theme_initial')) {
            $response = new RedirectResponse($this->request->getPathInfo());
            $expire = (new \DateTime('now', new \DateTimeZone('Europe/Paris')))->modify('+365 days');
            $response->headers->setCookie(Cookie::create('ADMIN_DARK_THEME', !empty($this->request->get('admin_dark_theme')) ? '1' : '0', $expire));
            $response->send();
        }

        if (!$_FILES && $this->request->get('entitylocale')) {
            $this->session->set('currentEntityLocale', $this->request->get('entitylocale'));
        }
    }
}
