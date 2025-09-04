<?php

declare(strict_types=1);

namespace App\Form\Validator;

use App\Entity\Security\User;
use App\Entity\Security\UserFront;
use Symfony\Component\Validator\Constraint;

/**
 * UniqUserEmail.
 *
 * @Annotation
 * @Target({"PROPERTY", "ANNOTATION"})
 */
class UniqUserEmail extends Constraint
{
    protected string $message = '';

    protected User|UserFront|null $user = null;
}
