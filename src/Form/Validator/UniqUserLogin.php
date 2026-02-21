<?php

declare(strict_types=1);

namespace App\Form\Validator;

use App\Entity\Security\User;
use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * UniqUserLogin.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[\Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class UniqUserLogin extends Constraint
{
    protected string $message = '';

    protected ?User $user = null;
}
