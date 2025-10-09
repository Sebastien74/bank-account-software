<?php

namespace App\Model;

use App\Service\CoreLocatorInterface;

class BaseModel
{
    protected static ?CoreLocatorInterface $coreLocator = null;

    /**
     * To set CoreLocator.
     */
    protected static function setLocator(CoreLocatorInterface $coreLocator): void
    {
        self::$coreLocator = $coreLocator;
    }
}