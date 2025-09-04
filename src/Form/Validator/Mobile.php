<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Mobile.
 *
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 */
class Mobile extends Constraint
{
    protected string $message = '';
}
