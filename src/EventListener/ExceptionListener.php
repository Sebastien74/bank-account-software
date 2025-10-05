<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Command\DoctrineCommand;
use App\Service\CoreLocatorInterface;
use Doctrine\DBAL\Exception\InvalidFieldNameException;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

/**
 * ExceptionListener.
 *
 * Listen event Exceptions
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ExceptionListener
{
    /**
     * ExceptionListener constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly DoctrineCommand $doctrineCommand,
    ) {
    }

    /**
     * onKernelException.
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $allowedIP = $this->coreLocator->checkIP();

        if ($allowedIP && $this->isDoctrineUpdateError($exception->getPrevious())) {
            $this->doctrineCommand->update();
        }
    }

    /**
     * Check if is doctrine error for update.
     */
    private function isDoctrineUpdateError(mixed $exception): bool
    {
        if ($exception instanceof InvalidFieldNameException) {
            return true;
        }

        $patterns = ['Entity of type', 'SQLSTATE', 'Column not found'];
        $excludedPatterns = ['Disk full', '42000', '23000', 'SQL syntax', 'Syntax error', 'server has gone away', 'Access denied', 'is not allowed to connect', 'check the manual', 'max_user_connections', 'connexion'];
        if ($exception && method_exists($exception, 'getMessage')) {
            foreach ($excludedPatterns as $pattern) {
                if (str_contains(strtolower($exception->getMessage()), strtolower($pattern))) {
                    return false;
                }
            }
            foreach ($patterns as $pattern) {
                if (preg_match('/'.$pattern.'/', $exception->getMessage()) && !str_contains($exception->getMessage(), '23000')) {
                    return true;
                }
            }
        }

        return false;
    }
}
