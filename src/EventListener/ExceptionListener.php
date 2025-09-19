<?php

declare(strict_types=1);

namespace App\EventListener;

use Psr\Cache\InvalidArgumentException;
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
    public function __construct() {
    }

    /**
     * onKernelException.
     */
    public function onKernelException(ExceptionEvent $event): void
    {

    }
}
