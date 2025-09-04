<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * UniqUserLogin.
 *
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 */
#[\Attribute] class UniqUserLogin extends Constraint
{
    protected string $message = '';
}
