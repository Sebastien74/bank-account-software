<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * SmartList.
 *
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 */
class SmartList extends Constraint
{
    protected string $message = '';
}