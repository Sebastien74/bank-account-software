<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * CaptchaHoneyPot.
 */
#[\Attribute]
class CaptchaHoneyPot extends Constraint
{
    public string $message = "";
}
