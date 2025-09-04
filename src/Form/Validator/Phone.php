<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Phone.
 *
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 */
class Phone extends Constraint
{
    protected string $message = '';
}
