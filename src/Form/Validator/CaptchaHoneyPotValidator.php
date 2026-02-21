<?php

declare(strict_types=1);

namespace App\Form\Validator;

use App\Service\CoreLocatorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * CaptchaHoneyPotValidator.
 *
 * Check if the captcha honey pot value is valid.
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
class CaptchaHoneyPotValidator extends ConstraintValidator
{
    /**
     * CaptchaHoneyPotValidator constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * Validate.
     *
     * @param mixed $value
     * @param Constraint $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!empty($value)) {
            $message = $this->coreLocator->translator()->trans('Invalid CaptchaHoneyPot.', [], 'validators');
            $this->context->buildViolation($message)->addViolation();
            $this->coreLocator->requestStack()->getSession()->getFlashBag()->get('captcha');
            $this->coreLocator->requestStack()->getSession()->getFlashBag()->add(
                'error',
                $this->coreLocator->translator()->trans("The captcha is invalid. Please reload the page and try again.", [], 'validators')
            );
        }
    }
}
