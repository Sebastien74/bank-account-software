<?php

declare(strict_types=1);

namespace App\Form\Validator;

use App\Service\CoreLocatorInterface;
use App\Service\CryptServiceInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * CaptchaTokenValidator.
 *
 * Check if captcha token value is valid.
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
class CaptchaTokenValidator extends ConstraintValidator
{
    private ?string $secretKey = '4d5d63a83bb68c298be7a212b2d939ab2b28fe39';

    /**
     * CaptchaTokenValidator constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface  $coreLocator,
        private readonly CryptServiceInterface $cryptService,
    ) {
        $this->secretKey = !empty($_ENV['APP_SECRET_KEY']) ? $_ENV['APP_SECRET_KEY'] : $this->secretKey;
    }

    /**
     * Validate
     *
     * @param mixed $value
     * @param Constraint $constraint
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        $output = $value ? $this->cryptService->execute($value, 'd') : null;
        if ($output !== $this->secretKey) {
            $message = $this->coreLocator->translator()->trans('Invalid CaptchaToken.', [], 'validators');
            $this->context->buildViolation($message)->addViolation();
            $this->coreLocator->request()->getSession()->getFlashBag()->get('captcha');
            $this->coreLocator->request()->getSession()->getFlashBag()->add(
                'error',
                $this->coreLocator->translator()->trans("The captcha is invalid. Please reload the page and try again.", [], 'validators')
            );
        }
    }
}
