<?php

declare(strict_types=1);

namespace App\Form\Validator;

use App\Entity\Security\User;
use App\Repository\Security\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * UniqUserEmailValidator.
 *
 * Check if User email already exists.
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
        /** @var User $user */
        $user = $this->context->getRoot()->getData();
        $existingUser = $this->userRepository->findOneBy(['email' => $value]);

        if (!$existingUser || is_object($existingUser) && method_exists($user, 'getId') && $existingUser->getId() === $user->getId()) {
            return;
        }

        $message = $this->translator->trans('This email already exists.', [], 'validators_cms');
        $this->context->buildViolation($message)->addViolation();
    }
}
