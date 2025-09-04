<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Core\Security;
use App\Entity\Core\Website;
use App\Entity\Security\User;
use App\Model\Core\WebsiteModel;
use App\Service\Core\CspNonceGenerator;
use App\Service\Interface\CoreLocatorInterface;
use Psr\Cache\InvalidArgumentException;
use Random\RandomException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * SecurityPolicySubscriber.
 *
 * To manage XSS protection
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class SecurityPolicySubscriber implements EventSubscriberInterface
{
    private const bool XSS_DENIED = true;
    private const string XSS_PATTERN = '/(<\s*script|on\w+\s*=|javascript:|<svg|<img|<iframe|<object|data:text\/html)/i';

    private Request $request;
    private ?string $uri = null;
    private ?string $requestUri = null;
    private ?string $host = null;
    private ?string $schemeAndHttpHost = null;
    private ?string $routeName = null;
    private Session $session;
    private bool $isMainRequest;
    private bool $inAdmin;

    /**
     * SecurityPolicySubscriber constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly CspNonceGenerator $nonceGenerator,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => 'addSecurityToResponse'];
    }

    /**
     * Adds the Content Security Policy header.
     *
     * @throws \Exception|InvalidArgumentException
     */
    public function addSecurityToResponse(ResponseEvent $event): void
    {
        $this->request = $event->getRequest();
        $this->uri = $this->request->getUri();
        $this->requestUri = $this->request->getRequestUri();
        $this->host = $this->request->getHost();
        $this->schemeAndHttpHost = $this->request->getSchemeAndHttpHost();
        $this->routeName = $this->request->get('_route');
        $this->session = $this->request->getSession();
        $this->isMainRequest = $event->isMainRequest();
        $this->inAdmin = (bool) preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $this->uri);

        $response = $event->getResponse();

        if ($this->request->cookies->get('SECURITY_ERROR')) {
            $response->headers->clearCookie('SECURITY_ERROR');
        }

        if ('front_clear_cache' === $this->routeName) {
            $nonce = $this->session->get('app_nonce');
            if ($nonce === $this->request->get('token')) {
                return;
            }
        } elseif (!$this->isMainRequest || str_contains($this->uri, '_wdt') || !$this->isMainRequest()) {
            return;
        }

        /** @var Website $website */
        $website = $this->coreLocator->em()->getRepository(Website::class)->findOneByHost();
        $security = $website instanceof WebsiteModel ? $website->security : null;
        $headers = $security instanceof Security && is_array($security->getHeaderData()) ? $security->getHeaderData() : [];

        $this->xssProtection();
        $this->setCacheControl();

        if ($this->coreLocator->tokenStorage()->getToken()) {
            $user = $this->coreLocator->tokenStorage()->getToken()->getUser();
            $this->isSecure($event, $website, $user);
            if ($user instanceof User) {
                $userKey = $user->getSecretKey();
                if (empty($_COOKIE['SECURITY_USER_SECRET']) && $this->coreLocator->authorizationChecker()->isGranted('ROLE_ADMIN')) {
                    $response->headers->setCookie(Cookie::create('SECURITY_USER_SECRET', $userKey));
                    $response->headers->setCookie(Cookie::create('SECURITY_IS_ADMIN', '1'));
                    $response->headers->setCookie(Cookie::create('SECURITY_TOKEN', $_ENV['SECURITY_TOKEN']));
                    $this->session->set('SECURITY_USER_SECRET', $userKey);
                    $this->session->set('SECURITY_IS_ADMIN', true);
                    $this->session->set('SECURITY_TOKEN', $_ENV['SECURITY_TOKEN']);
                }
            }
            if ($user instanceof User) {
                $this->checkAdmin($user, $event);
            }
        }

        if (in_array('x-frame-options-sameorigin', $headers) && in_array('x-frame-options-deny', $headers)) {
            unset($headers[array_search('x-frame-options-sameorigin', $headers)]);
        }
        if (in_array('cross-origin-embedder-policy', $headers) && in_array('cross-origin-resource-policy', $headers)) {
            unset($headers[array_search('cross-origin-resource-policy', $headers)]);
        }
        foreach ($headers as $header) {
            $config = $this->header($header);
            if (!empty($config)) {
                $response->headers->set($config['key'], $config['values']);
            }
        }

        $this->removeSensitiveHeader($response);
    }

    /**
     * Check if is mainRequest.
     */
    private function isMainRequest(): bool
    {
        $excludedRoutes = [
            'browser_robots',
            'browser_web_manifest',
            'browser_sitemap',
            'browser_config',
            'browser_ie_alert',
        ];

        $routeName = $this->request->attributes->get('_route');
        if ('_wdt' === $routeName || in_array($routeName, $excludedRoutes)) {
            return false;
        }

        return true;
    }

    /**
     * Ti remove sensitive header sensitive information.
     */
    private function removeSensitiveHeader(Response $response): void
    {
        $headersToRemove = [
            'X-Powered-By',
            'Server',
            'X-Drupal-Cache',
            'X-Generator',
            'X-AspNet-Version',
            'X-AspNetMvc-Version',
            'X-Runtime',
            'X-Version',
            'X-Env',
            'X-Application-Context',
            'Via',
            'X-Cache',
            'CF-Cache-Status',
        ];

        foreach ($headersToRemove as $header) {
            $response->headers->remove($header);
        }
    }

    /**
     * Set Cache Control.
     */
    private function setCacheControl(): void
    {
        if (preg_match('/\/js\/routing/', $this->uri)) {
            header('Cache-Control: no-cache, must-revalidate');
            /* HTTP 1.1 */
            header('Pragma: no-cache');
            /* HTTP 1.0 */
            header('Cache-Control: max-age=2592000');
            /* 30days (60sec * 60min * 24hours * 30days) */
        }
    }

    /**
     * Check if is secure website & redirect if User isn't connected.
     */
    private function isSecure(ResponseEvent $responseEvent, ?WebsiteModel $website = null, mixed $user = null): void
    {
        $allowedRoutes = [
            'security_front_login',
            'security_front_password_request',
            'security_front_password_confirm',
            'security_front_register',
            'front_webmaster_toolbox',
        ];

        if (!$this->isMainRequest
            || preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $this->uri)
            || preg_match('/\/secure\/user/', $this->uri)
            || preg_match('/\/front\//', $this->uri)
            || in_array($this->routeName, $allowedRoutes)) {
            return;
        }

        $website = $website instanceof WebsiteModel ? $website : $this->coreLocator->em()->getRepository(Website::class)->findOneByHost($this->host);
        if ($website->entity->getSecurity()->isSecureWebsite() && !$user instanceof User) {
            $responseEvent->setResponse(new RedirectResponse($this->coreLocator->router()->generate('security_front_login')));
        }
    }

    /**
     * Check if User is allowed to edit WebsiteModel.
     *
     * @throws InvalidArgumentException
     */
    private function checkAdmin(User $user, ResponseEvent $responseEvent): void
    {
        if ($this->inAdmin && !str_contains($this->requestUri, '_switch_user') && !str_contains($this->requestUri, '/medias/cache/')) {
            $website = $this->coreLocator->website();
            $websiteId = $website?->id;
            $allowed = false;
            foreach ($user->getWebsites() as $userWebsite) {
                if ($userWebsite->getId() === $websiteId) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed && !in_array('ROLE_INTERNAL', $user->getRoles())) {
                if (count($user->getWebsites()) > 0) {
                    $responseEvent->setResponse(new RedirectResponse($this->coreLocator->router()->generate('admin_dashboard', ['website' => $user->getWebsites()[0]->getId()])));
                } else {
                    header('Location: '.$this->schemeAndHttpHost.'/denied.php?site=true');
                    exit;
                }
            }
        }
    }

    /**
     * Checks and blocks potential XSS attacks globally across the application.
     */
    private function xssProtection(): void
    {
        // Skip protection in admin area or for trusted internal Symfony fragment
        if ($this->inAdmin || str_contains($this->requestUri, 'javascript-critical-errors')) {
            return;
        }

        // Early return for known safe/internal Symfony paths
        $path = $this->request->getPathInfo();
        if (
            str_starts_with($path, '/_fragment') ||
            str_starts_with($path, '/_wdt') ||
            str_starts_with($path, '/_profiler') ||
            str_starts_with($path, '/_error')
        ) {
            return;
        }

        // Check GET parameters
        foreach ($this->request->query->all() as $value) {
            if ($this->containsXss($value)) {
                // Malicious content detected in query parameters
                if (self::XSS_DENIED) {
                    $message = $this->coreLocator->isDebug() ? 'Access denied query parameters' : 'Access denied';
                    throw new AccessDeniedHttpException($message);
                }
            }
        }
        // Check POST parameters
        foreach ($this->request->request->all() as $value) {
            if ($this->containsXss($value)) {
                // Malicious content detected in POST data
                if (self::XSS_DENIED) {
                    $message = $this->coreLocator->isDebug() ? 'Access denied POST data' : 'Access denied';
                    throw new AccessDeniedHttpException($message);
                }
            }
        }
        // Optionally check full URI string
        if ($this->containsXss($this->request->getRequestUri())) {
            // Malicious content detected in request URI
            if (self::XSS_DENIED) {
                $message = $this->coreLocator->isDebug() ? 'Access denied request URI' : 'Access denied';
                throw new AccessDeniedHttpException($message);
            }
        }
    }

    /**
     * Detects if the given value contains potential XSS patterns.
     */
    private function containsXss(mixed $value): bool
    {
        if (!is_scalar($value)) {
            return false;
        }

        // Normalize the input (decode up to 2 levels)
        $decoded = urldecode($value);
        if ($decoded !== $value) {
            $decoded = urldecode($decoded); // handles double-encoded payloads
        }

        return (bool) preg_match(self::XSS_PATTERN, $decoded);
    }

    /**
     * Header configuration.
     *
     * @throws RandomException
     */
    private function header(string $header): array
    {
        if ($this->inAdmin) {
            return [];
        }

        $headers = [
            'strict-transport-security' => ['key' => 'Strict-Transport-Security', 'values' => 'max-age=31536000; includeSubDomains; preload'],
            'permissions-policy' => ['key' => 'Permissions-Policy', 'values' => 'geolocation=(), microphone=(), camera=(), payment=()'],
            'content-security-policy' => ['key' => 'Content-Security-Policy', 'values' => $this->securityPolicy()],
            'referrer-policy' => ['key' => 'Referrer-Policy', 'values' => 'strict-origin-when-cross-origin'],
            'cross-origin-embedder-policy' => ['key' => 'Cross-Origin-Embedder-Policy', 'values' => 'unsafe-none'],
            'cross-origin-resource-policy' => ['key' => 'Cross-Origin-Resource-Policy', 'values' => 'cross-origin'],
            'x-xss-protection' => ['key' => 'X-XSS-Protection', 'values' => '1; mode=block'], /* if not work uncomment line in .htaccess */
            'x-ua-compatible' => ['key' => 'X-UA-Compatible', 'values' => 'IE=edge,chrome=1'],
            'content-type-options-nosniff' => ['key' => 'X-Content-Type-Options', 'values' => 'nosniff'], /* if not work uncomment line in .htaccess */
            'x-frame-options-deny' => ['key' => 'X-Frame-Options', 'values' => 'DENY'],  /* if not work uncomment line in .htaccess */
            'x-frame-options-sameorigin' => ['key' => 'X-Frame-Options', 'values' => 'SAMEORIGIN'],  /* if not work uncomment line in .htaccess */
            'x-permitted-cross-domain-policies' => ['key' => 'X-Permitted-Cross-Domain-Policies', 'values' => 'none'],
            'cross-origin-opener-policy' => ['key' => 'Cross-Origin-Opener-Policy', 'values' => 'none'],
            'access-control-allow-origin' => ['key' => 'Access-Control-Allow-Origin', 'values' => $this->schemeAndHttpHost],
        ];

        if ($this->coreLocator->isDebug()) {
            unset($headers['content-security-policy']);
        }

        return !empty($headers[$header]) ? $headers[$header] : [];
    }

    /**
     * To set Content-Security-Policy.
     *
     * @throws RandomException
     */
    private function securityPolicy(): string
    {
        $nonce = $this->nonceGenerator->getNonce();
        $matomo = 'https://matomo.agence-felix.fr';

        // Allowed external sources for inline and third-party scripts
        $allowedScriptDomains = [
            "'self'",
            "'nonce-{$nonce}'",
            'https://www.googletagmanager.com',
            'https://www.google-analytics.com',
            'https://www.youtube.com',
            'https://static.axept.io',
            'https://cdn.matomo.cloud',
            $matomo,
        ];

        // Allowed frame
        $allowedFrame = [
            "'self'",
            'https://www.youtube.com',
            'https://www.googletagmanager.com',
        ];

        // Allowed domains for XHR/fetch/WebSocket/API connections
        $allowedConnectDomains = [
            "'self'",
            'https://www.youtube.com',
            'https://www.google.com',
            'https://www.google-analytics.com',
            'https://region1.google-analytics.com',
            'https://cdn.matomo.cloud',
            'https://client.axept.io',
            'https://api.axept.io',
            'https://axeptio.imgix.net',
            $matomo,
        ];

        // Allowed script els
        $scriptEls = [
            "'self'",
            "data:",
            "'unsafe-inline'",
            'https://www.youtube.com',
            'https://www.google-analytics.com',
            'https://www.googletagmanager.com',
            'https://fonts.googleapis.com',
            'https://static.axept.io',
            $matomo,
        ];

        // Allowed sources for images
        $allowedImageDomains = [
            "'self'",
            "data:",
            "blob:",
            'https://www.youtube.com',
            'https://www.google-analytics.com',
            'https://www.googletagmanager.com',
            'https://www.google.com',
            'https://i.ytimg.com',
            'https://cdn.matomo.cloud',
            'https://img.youtube.com',
            'https://favicons.axept.io',
            $matomo,
        ];

        // Allowed style src
        $allowedStyleSrc = [
            "'self'",
            "'unsafe-inline'",
            "data:",
            'https://use.typekit.net',
        ];

        foreach (range('a', 'z') as $subdomain) {
            $allowedImageDomains[] = "https://{$subdomain}.basemaps.cartocdn.com";
            $allowedImageDomains[] = "https://{$subdomain}.clarity.ms";
            $allowedConnectDomains[] = "https://{$subdomain}.clarity.ms";
            $allowedScriptDomains[] = "https://{$subdomain}.clarity.ms";
            $scriptEls[] = "https://{$subdomain}.clarity.ms";
            $allowedStyleSrc[] = "https://{$subdomain}.typekit.net ";
        }

        // Allowed sources for fonts
        $fontsDomains = [
            "'self'",
            "data:",
            'https://fonts.gstatic.com',
            'https://fonts.googleapis.com',
            'https://use.typekit.net',
        ];

        // CSP assembled with nonce for both scripts and styles
        return "default-src 'self'; " .
            "frame-src ".implode(' ', $allowedFrame)."; ".
            "script-src ".implode(' ', $allowedScriptDomains)."; ".
            "connect-src ".implode(' ', $allowedConnectDomains)."; ".
            "img-src ".implode(' ', $allowedImageDomains)."; ".
            "media-src 'self' data:; ".
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; https://use.typekit.net;".
            "style-src-elem ".implode(' ', $allowedStyleSrc)."; ".
            "script-src-elem ".implode(' ', $scriptEls)."; ".
            "font-src ".implode(' ', $fontsDomains)."; ".
            "object-src 'none'; base-uri 'self'; form-action 'self'; ".
            "upgrade-insecure-requests;";
    }
}
