<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * UniqFile.
 *
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 */
class UniqFile extends Constraint
{
    protected string $message = '';
}
