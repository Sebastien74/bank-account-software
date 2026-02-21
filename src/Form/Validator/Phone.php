<?php

declare(strict_types=1);

namespace App\Form\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * Phone.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[\Attribute]
class Phone extends Constraint
{
    protected string $message = '';
}
