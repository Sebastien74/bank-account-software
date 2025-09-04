<?php

declare(strict_types=1);

namespace App\Model;

use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Intl\Countries;

/**
 * FunctionsModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class FunctionModel
{
    /**
     * Check if is phone.
     */
    protected static function isPhone(mixed $var): bool
    {
        foreach (Countries::getNames() as $code => $name) {
            $phoneUtil = PhoneNumberUtil::getInstance();
            try {
                if ($phoneUtil->parse($var, strtoupper($code))) {
                    return true;
                }
            } catch (\Exception $exception) {
                return false;
            }
        }

        return false;
    }
}
