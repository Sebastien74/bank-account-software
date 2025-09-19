<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Security\User;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;

/**
 * ExceptionController.
 *
 * Manage render Exceptions
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ExceptionController extends BaseController
{
    protected int $statusCode = 0;
    protected bool $isDebug = false;

    /**
     * Page render.
     */
    public function showAction(
        Request $request,
        FlattenException|\Exception $exception,
        bool $isDebug,
        string $projectDir,
        ?DebugLoggerInterface $logger = null,
    ): Response {

        $this->isDebug = $isDebug;

        if (!$this->isDebug && preg_match('/\/admin-'.$_ENV['SECURITY_TOKEN'].'/', $request->getUri()) && !$this->getUser() instanceof User) {
            return $this->redirect($request->getSchemeAndHttpHost());
        }

        $this->statusCode = $exception->getStatusCode();
        $this->statusCode = 0 === $this->statusCode ? 500 : $this->statusCode;

        $arguments = $this->setArguments($exception, $logger);
        $template = $this->getTemplate($projectDir);

        return $this->render($template, $arguments);
    }
    /**
     * Get template.
     */
    private function getTemplate(string $projectDir): string
    {
        $filesystem = new Filesystem();
        $dirname = $projectDir.'\templates\bundles\TwigBundle\Exception\\';
        $isNotFound = 404 === $this->statusCode;
        $isForbidden = 403 === $this->statusCode || 401 === $this->statusCode;
        $isDevEnv = $_ENV['APP_ENV'] === 'local' || $_ENV['APP_ENV'] === 'dev';
        $displayStackTraces = true === (bool)$_ENV['APP_DEBUG'] && $isDevEnv && !$isNotFound && !$isForbidden;

        if ($displayStackTraces) {
            return '@Twig/Exception/stack-traces.html.twig';
        } elseif ($filesystem->exists($dirname.'exception_full.html.twig')) {
            return '@Twig/Exception/exception_full.html.twig';
        } elseif ($filesystem->exists($dirname.'error-'.$this->statusCode.'.html.twig')) {
            return '@Twig/Exception/error-'.$this->statusCode.'.html.twig';
        }

        return '@Twig/Exception/error.html.twig';
    }

    /**
     * Set page arguments.
     */
    private function setArguments(
        FlattenException|\Exception $exception,
        ?DebugLoggerInterface $logger = null,
    ): array {

        $arguments['is_debug'] = $this->isDebug;
        $arguments['logger'] = $logger;
        $arguments['status_code'] = $this->statusCode;
        $arguments['status_text'] = $exception->getMessage();
        $arguments['exception'] = $exception;
        $arguments['currentContent'] = null;

        return array_merge($this->defaultArguments(), $arguments);
    }
}
