<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * CaptchaToken.
 */
#[\Attribute]
class CaptchaToken extends Constraint
{
    public string $message = "";
}
