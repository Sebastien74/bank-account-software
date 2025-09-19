<?php

declare(strict_types=1);

namespace App\Service;

use Exception;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * MailerService.
 *
 * To send email
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 *
 * @doc https://symfony.com/doc/current/mailer.html
 */
class MailerService
{
    private const bool SET_HEADER = false;

    private ?string $envName;
    private ?string $subject = null;
    private ?string $name = null;
    private ?string $from = 'fournier.sebastien@outlook.com';
    private array $to = ['fournier.sebastien@outlook.com'];
    private array $cc = [];
    private ?string $replyTo = 'fournier.sebastien@outlook.com';
    private ?string $baseTemplate = 'base';
    private ?string $template = 'core/email/email.html.twig';
    private array $arguments = [];
    private array $attachments = [];
    private ?string $locale = null;
    private ?string $host = null;
    private ?string $schemeAndHttpHost = null;

    /**
     * MailerService constructor.
     */
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly CoreLocatorInterface $coreLocator,
    ) {
        $this->envName = 'prod' !== $_ENV['APP_ENV'] ? strtoupper($_ENV['APP_ENV']) : null;
    }

    /**
     * Send email.
     *
     * @throws Exception
     */
    public function send(): object
    {
        $this->default();

        /** To send email */
        $logger = new Logger('symfony_mailer');

//        try {
            $email = (new TemplatedEmail());
            if (self::SET_HEADER) {
                $this->setHeaders($email);
            }
            $this->setMessage($email);
            $this->mailer->send($email);
//        } catch (TransportExceptionInterface|Exception $exception) {
//            dd($exception);
//            $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/mailer-critical.log', 10, Level::Info));
//            $logger->critical($exception->getMessage().' at '.get_class($this));
//            $message = $this->coreLocator->isDebug() ? $exception->getMessage().' at '.get_class($this)
//                : $this->coreLocator->translator()->trans('Une erreur est survenue. Veuillez réessayer.', [], 'front');
//            return (object) [
//                'success' => false,
//                'exception' => $exception,
//                'message' => $message,
//            ];
//        }
        foreach ($this->to as $emailAddress) {
            $logger->pushHandler(new RotatingFileHandler($this->coreLocator->logDir().'/mailer.log', 10, Level::Info));
            $logger->info('Send to '.$emailAddress.' from '.$this->from.' at '.(new \DateTime('now', new \DateTimeZone('Europe/Paris')))->format('Y-m-d H:i:s'));
        }

        return (object) [
            'success' => true,
        ];
    }

    /**
     * Set default values by WebsiteModel information.
     */
    private function default(): void
    {
        $request = $this->coreLocator->request();

        /* To set locale */
        $this->locale = !$this->locale ? $request->getLocale() : $this->locale;
        if ($this->locale) {
            $this->coreLocator->translator()->setLocale($this->locale);
        }

        $this->schemeAndHttpHost = $this->coreLocator->schemeAndHttpHost();
        $this->name = $_ENV['APP_COMPANY_NAME'];
        $this->from = $_ENV['EMAIL_FROM'];
        $this->replyTo = $_ENV['EMAIL_NO_REPLY'];
        $this->host = $request->getHost();
    }

    /**
     * Set headers.
     */
    private function setHeaders(TemplatedEmail $email): void
    {
//        $headers = $email->getHeaders();
//        $messageId = $this->host ? md5($this->host) . "@" . $this->host : md5(uniqid());
//        $headers->addIdHeader('Message-ID', $messageId);
//        $headers->addTextHeader('MIME-Version', '1.0');
//        $headers->addTextHeader('X-MailerService', 'PHP v' . phpversion());
    }

    /**
     * Set message.
     *
     * @throws Exception
     */
    private function setMessage(TemplatedEmail $email): void
    {
        $this->arguments['base'] = $this->baseTemplate;
        $this->arguments['attachments'] = $this->attachments;
        $this->arguments['host'] = $this->host;
        $this->arguments['schemeAndHttpHost'] = $this->schemeAndHttpHost;
        $this->arguments['locale'] = $this->locale;

        if (empty($this->to)) {
            $this->to = $_ENV['EMAIL_TO'];
        }

        $subject = $this->envName ? '['.$this->envName.'] - '.$this->subject : $this->subject;

        $email->subject($subject);
        $email->date(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
        $email->from(new Address($this->from, $this->name));
        foreach ($this->to as $key => $emailAddress) {
            $method = $key > 0 ? 'addTo' : 'to';
            $email->$method(new Address($emailAddress));
        }
        foreach ($this->cc as $key => $emailAddress) {
            $method = $key > 0 ? 'addCc' : 'cc';
            $email->$method(new Address($emailAddress));
        }
        if ($this->replyTo && $this->replyTo !== $this->from) {
            $email->replyTo(new Address($this->replyTo));
        }
        $email->generateMessageId();
        $email->priority(Email::PRIORITY_HIGH);
        $email->htmlTemplate($this->template);
        $email->textTemplate(strip_tags(html_entity_decode($this->template)));
        $email->context($this->arguments);

        foreach ($this->attachments as $attachment) {
            $email->attachFromPath($attachment);
        }
    }

    /**
     * Set subject.
     */
    public function setSubject(?string $subject = null): void
    {
        if ($subject) {
            $subject = strip_tags($subject);
        }

        $this->subject = $subject;
    }

    /**
     * Set name.
     */
    public function setName(?string $name = null): void
    {
        $this->name = $name ?: $this->name;
    }

    /**
     * Set from.
     */
    public function setFrom(?string $from = null): void
    {
        $this->from = $from;
    }

    /**
     * Set to.
     */
    public function setTo(array $to = []): void
    {
        $emails = [];
        foreach ($to as $item) {
            $matches = explode(',', $item);
            $emails = array_merge($emails, $matches);
        }
        $this->to = array_unique($emails);
    }

    /**
     * Set cc.
     */
    public function setCc(array $cc = []): void
    {
        $emails = [];
        foreach ($cc as $item) {
            $matches = explode(',', $item);
            $emails = array_merge($emails, $matches);
        }
        $this->cc = array_unique($emails);
    }

    /**
     * Set replyTo.
     */
    public function setReplyTo(?string $replyTo = null): void
    {
        $this->replyTo = $replyTo;
    }

    /**
     * Set base template.
     */
    public function setBaseTemplate(?string $baseTemplate = null): void
    {
        $this->baseTemplate = $baseTemplate;
    }

    /**
     * Set template.
     */
    public function setTemplate(?string $template = null): void
    {
        $this->template = $template;
    }

    /**
     * Set arguments.
     */
    public function setArguments(array $arguments = []): void
    {
        $this->arguments = $arguments;
    }

    /**
     * Set attachments.
     */
    public function setAttachments(array $attachments = []): void
    {
        $this->attachments = $attachments;
    }

    /**
     * Set locale.
     */
    public function setLocale(?string $locale = null): void
    {
        $this->locale = $locale;
    }
}
