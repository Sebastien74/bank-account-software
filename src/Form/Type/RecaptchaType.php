<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Form\Validator as Validators;
use App\Service\CoreLocatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * RecaptchaType.
 *
 * @author Sébastien FOURNIER <contact@sebastien-fournier.com>
 */
class RecaptchaType extends AbstractType
{
    private const string APP_SECRET_KEY = '4d5d63a83bb68c298be7a212b2d939ab2b28fe39';
    private const string APP_PUBLIC_KEY = '06efe6843c7322ef3c39aead28be85f4581bc567';

    private ?string $secretKey;
    private ?string $publicKey;
    private int $tokenLength;

    /**
     * RecaptchaType constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
        $this->secretKey = !empty($_ENV['APP_SECRET_KEY']) ? $_ENV['APP_SECRET_KEY'] : self::APP_SECRET_KEY;
        $this->publicKey = !empty($_ENV['APP_PUBLIC_KEY']) ? $_ENV['APP_PUBLIC_KEY'] : self::APP_PUBLIC_KEY;
        $this->tokenLength = !empty($_ENV['CAPTCHA_TOKEN_LENGTH']) ? intval($_ENV['CAPTCHA_TOKEN_LENGTH']) : 16;
    }

    /**
     * {@inheritDoc}
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($this->secretKey && $this->publicKey) {

            $builder->add('honeypot', Type\TextType::class, [
                'label_html' => true,
                'label' => $this->coreLocator->translator()->trans('Required field'),
                'mapped' => false,
                'required' => false,
                'constraints' => [new Validators\CaptchaHoneyPot()]
            ]);

            $builder->add('honeypot_token', Type\TextType::class, [
                'label_html' => true,
                'label' => $this->coreLocator->translator()->trans('Required field'),
                'mapped' => false,
                'data' => $this->generateToken(),
                'constraints' => [new Validators\CaptchaToken()]
            ]);
        }
    }

    /**
     * To generate token
     *
     * @return string
     */
    private function generateToken(): string
    {
        $key = hash('sha256', $this->secretKey);
        $iv = substr(hash('sha256', $this->publicKey), 0, $this->tokenLength);
        return base64_encode(openssl_encrypt($this->secretKey, "AES-256-CBC", $key, 0, $iv));
    }

    /**
     * {@inheritDoc}
     *
     * @param OptionsResolver $resolver
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
            'mapped' => false,
            'translation_domain' => 'core_form',
        ]);
    }
}
