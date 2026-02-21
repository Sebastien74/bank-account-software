<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\Security\User;
use App\Form\Manager\RecaptchaInterface;
use App\Repository\Security\UserRepository;
use App\Service\CoreLocatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception as SecurityException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * BaseAuthenticator.
 *
 * Manage recaptcha security authenticate post.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class BaseAuthenticator
{
    private TranslatorInterface $translator;

    private EntityManagerInterface $entityManager;

    private string $loginRoute = '';

    private string $loginType = '';

    private string $classname = '';

    private ?object $userRepository;

    private ?object $user = null;

    private array $credentials = [];

    /**
     * BaseAuthenticator constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly RecaptchaInterface $recaptcha,
        private readonly CsrfTokenManagerInterface $csrfTokenManager,
    ) {
        $this->translator = $this->coreLocator->translator();
        $this->entityManager = $this->coreLocator->em();
    }

    /**
     * Check if is valid POST.
     *
     * @throws InvalidArgumentException
     */
    public function supports(Request $request): ?bool
    {
        // Le nom de route est toujours dans les attributs de la requête (pas dans le POST)
        $currentRoute = $request->attributes->get('_route');

        if ($currentRoute === $this->loginRoute && $request->isMethod('POST')) {
            $this->setCredentials($request);
            if (!$this->credentials['username']) {
                $message = $this->translator->trans('Authentication credentials could not be found.', [], 'security');
                throw new SecurityException\AuthenticationCredentialsNotFoundException($message);
            }
        }

        return $currentRoute === $this->loginRoute && $request->isMethod('POST');
    }

    /**
     * authenticate.
     *
     * @throws Exception|InvalidArgumentException
     */
    public function authenticate(Request $request): Passport
    {
        $this->setCredentials($request);

        if ($this->credentials['username']) {
            $this->user = $this->entityManager->getRepository($this->classname)->loadUserByIdentifier($this->credentials['username']);
            if (!$this->user) {
                throw new SecurityException\UserNotFoundException();
            }
        }

        $this->checkRecaptcha($request);
        $this->checkActive();
        $this->checkCsrfToken($request);

        $passport = new Passport(
            new UserBadge($this->credentials['username'], [$this->userRepository, 'loadUserByIdentifier']),
            new PasswordCredentials($this->credentials['password'])
        );
        $passport->addBadge(new CsrfTokenBadge('authenticate', $this->credentials['csrf_token']));

        $rememberMe = new RememberMeBadge();
        $rememberMe->enable();
        $passport->addBadge($rememberMe);

        return $passport;
    }

    /**
     * onAuthenticationSuccess.
     *
     * @throws SessionNotFoundException
     */
    public function onAuthenticationSuccess(Request $request): void
    {
        $request->getSession()->set('onAuthenticationSuccess', true);
    }

    /**
     * onAuthenticationFailure.
     *
     * @throws SessionNotFoundException|RouteNotFoundException|MissingMandatoryParametersException|InvalidParameterException
     */
    public function onAuthenticationFailure(Request $request, SecurityException\AuthenticationException $exception, ?string $route = null): ?Response
    {
        if ($exception instanceof SecurityException\TooManyLoginAttemptsAuthenticationException) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        } elseif (!$this->user) {
            $message = $this->translator->trans('Authentication credentials could not be found.', [], 'security');
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, new SecurityException\AuthenticationCredentialsNotFoundException($message));
        } else {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, $exception);
        }

        return $route ? new RedirectResponse($this->coreLocator->router()->generate($route)) : null;
    }

    /**
     * start.
     *
     * @throws RouteNotFoundException|MissingMandatoryParametersException|InvalidParameterException
     */
    public function start(
        Request $request,
        string $route,
        string $loginRoute,
        ?SecurityException\AuthenticationException $authException = null
    ): RedirectResponse|JsonResponse {

        $isInvalid = $authException instanceof SecurityException\InvalidCsrfTokenException
            || $authException instanceof SecurityException\CustomUserMessageAccountStatusException
            || $authException instanceof SecurityException\AuthenticationCredentialsNotFoundException;

        if ($this->coreLocator->user() && $authException instanceof SecurityException\InsufficientAuthenticationException) {
            $indAmin = preg_match('/\/back-'.$_ENV['SECURITY_TOKEN'].'/', $request->getUri());
            $routeName = $indAmin ? 'security_login' : 'security_front_login';
            return new RedirectResponse($this->coreLocator->router()->generate('app_logout', ['route_name' => $routeName]));
        }

        if ($isInvalid || is_object($authException) && !$request->getUser() && 403 === $authException->getPrevious()->getCode()) {
            if ($request->isMethod('POST') && $authException instanceof SecurityException\AuthenticationCredentialsNotFoundException) {
                $response = new RedirectResponse($this->coreLocator->router()->generate($loginRoute));
                $this->coreLocator->request()->getSession()->getFlashBag()->add('error', $this->coreLocator->translator()->trans($authException->getMessage(), [], 'security'));
                return $response;
            }
            $response = new RedirectResponse($this->coreLocator->schemeAndHttpHost().'/denied');
            if ('security_front_forms' === $route) {
                $response->headers->setCookie(Cookie::create('SECURITY_ERROR', $this->coreLocator->translator()->trans($authException->getMessageKey(), [], 'security')));
            }
            return $response;
        }

        $data = [
            'message' => 'Authentication Required',
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * To get credentials.
     *
     * @throws Exception
     */
    public function getCredentials(): array
    {
        return $this->credentials;
    }

    /**
     * To set credentials.
     *
     * @throws InvalidArgumentException|SessionNotFoundException|BadRequestException
     */
    public function setCredentials(Request $request): void
    {
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $request->request->get($this->loginType));
        $post = !empty($request->request->all('registration')) ? $request->request->all('registration') : $request->request->all();

        $this->credentials['csrf_token'] = !empty($post['_csrf_token']) ? $post['_csrf_token'] : null;
        $this->credentials['username'] = !empty($post[$this->loginType]) ? $post[$this->loginType] : null;
        $this->credentials['password'] = !empty($post['plainPassword']['first']) ? $post['plainPassword']['first'] : (!empty($post['_password']) ? $post['_password'] : '.');

        if (empty($this->credentials['csrf_token']) && !empty($this->credentials['username'])) {
            $user = $this->entityManager->getRepository($this->classname)->loadUserByIdentifier($this->credentials['username']);
            if ($user instanceof User) {
                $authenticatedToken = new UsernamePasswordToken($user, 'main', $user->getRoles());
                $this->credentials['csrf_token'] = $this->csrfTokenManager->getToken($authenticatedToken->getUserIdentifier())->getValue();
            }
        }
    }

    /**
     * To set a login route.
     */
    public function setLoginRoute(string $route): void
    {
        $this->loginRoute = $route;
    }

    /**
     * To set a login type.
     */
    public function setLoginType(string $classname): void
    {
        $this->loginType = $classname;
    }

    /**
     * To set a classname.
     */
    public function setClassname(string $classname): void
    {
        $this->classname = $classname;
    }

    /**
     * To set user Repository.
     */
    public function setUserRepository(UserRepository $userRepository): void
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Check recaptcha.
     *
     * @throws Exception
     */
    public function checkRecaptcha(Request $request, bool $asResponse = false, ?FormInterface $form = null): bool
    {
        if (!$this->recaptcha->execute($request, true, $form)) {
            if ($asResponse) {
                return false;
            }
            $message = $this->translator->trans('The captcha is invalid. Please reload the page and try again.', [], 'security_cms');
            throw new SecurityException\CustomUserMessageAccountStatusException($message);
        }

        return true;
    }

    /**
     * To check if an account is active.
     */
    public function checkActive(): void
    {
        $isUser = $this->user instanceof User;
        if ($isUser && !$this->user->isActive()) {
            $message = $this->getInactiveMessage();
            throw new SecurityException\CustomUserMessageAccountStatusException($message);
        }
    }

    /**
     * To get an inactive message.
     */
    public function getInactiveMessage(): string
    {
        return $this->translator->trans('Your account is not activated.', [], 'security_cms');
    }

    /**
     * To check csrf token.
     */
    public function checkCsrfToken(Request $request): void
    {
        $token = new CsrfToken('authenticate', $this->credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            $request->getSession()->set(SecurityRequestAttributes::AUTHENTICATION_ERROR, new SecurityException\InvalidCsrfTokenException());
            throw new SecurityException\InvalidCsrfTokenException();
        }
    }

    /**
     * To log a message.
     */
    private function logger(Request $request): void
    {
        $logger = new Logger('SECURITY_FORM');
        $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/security.log', 10, Level::Critical));
        $logger->critical('Recaptcha security. IP register :'.$request->getClientIp());
    }
}
