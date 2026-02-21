<?php

declare(strict_types=1);

namespace App\Form\Manager;

use App\Service\CoreLocatorInterface;
use App\Service\CryptServiceInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * RecaptchaManager.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class RecaptchaManager implements RecaptchaInterface
{
    private const string APP_SECRET_KEY = '4d5d63a83bb68c298be7a212b2d939ab2b28fe39';

    /**
     * ResetPasswordManager constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly CryptServiceInterface $cryptService,
    )
    {
    }

    public function execute(Request $request, bool $flashBag = false, ?FormInterface $form = null): bool
    {
        $formSecurityKey = !empty($_ENV['APP_SECRET_KEY']) ? $_ENV['APP_SECRET_KEY'] : self::APP_SECRET_KEY;
        $fieldsHo = $form && $form->getName() ? $request->request->all($form->getName())['secure'] : $request->request->all('secure');
        $fieldHo = $fieldsHo['honeypot_token'] ?? '';
        $fieldHoEntitled = $fieldsHo['honeypot'] ?? '';

        $message = $this->coreLocator->translator()->trans('The captcha is invalid. Please reload the page and try again.', [], 'security_cms');
        $session = $this->coreLocator->request()->getSession();

        if (!empty($fieldHo) && empty($fieldHoEntitled)) {
            $honeyPost = $this->cryptService->execute($fieldHo, 'd');
            if ($honeyPost && urldecode($honeyPost) != $formSecurityKey) {
                $this->logger($request);
                $session->getFlashBag()->add('error', $message);
                return false;
            }
        } else {
            if ($flashBag) {
                $session->getFlashBag()->add('error', $message);
            }
            $this->logger($request);
            return false;
        }

        return true;
    }

    /**
     * To log message.
     */
    private function logger(Request $request): void
    {
        $logger = new Logger('SECURITY_FORM');
        $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/recaptcha.log', 10, Level::Critical));
        $logger->critical('Recaptcha security. IP register :'.$request->getClientIp());
    }
}
