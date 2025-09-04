<?php

declare(strict_types=1);

namespace App\Form\Validator;

use App\Entity\Security\User;
use App\Entity\Security\UserRequest;
use App\Form\Model\Security\Admin\RegistrationFormModel;
use App\Repository\Security\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * UniqUserEmailValidator.
 *
 * Check if User email already exist
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UniqUserEmailValidator extends ConstraintValidator
{
    /**
     * UniqUserEmailValidator constructor.
     */
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * Validate.
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        /* @var $constraint UniqUserEmail */

        /** @var User|UserRequest $user */
        $user = $this->context->getRoot()->getData();
        $repository = $user instanceof RegistrationFormModel || $user instanceof User ? $this->userRepository : false;
        $existingUser = $repository ? $repository->findOneBy(['email' => $value]) : false;

        if (!$existingUser || is_object($existingUser) && method_exists($user, 'getId') && $existingUser->getId() === $user->getId()) {
            return;
        }

        $message = $this->translator->trans('Cet email existe déjà.', [], 'validators_cms');
        $this->context->buildViolation($message)->addViolation();
    }
}
