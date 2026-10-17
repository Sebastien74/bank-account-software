<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Form\Validator as Validators;
use App\Service\CoreLocatorInterface;
use App\Service\CryptServiceInterface;
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

    /**
     * RecaptchaType constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly CryptServiceInterface $cryptService,
    ) {
        $this->secretKey = !empty($_ENV['APP_SECRET_KEY']) ? $_ENV['APP_SECRET_KEY'] : self::APP_SECRET_KEY;
        $this->publicKey = !empty($_ENV['APP_PUBLIC_KEY']) ? $_ENV['APP_PUBLIC_KEY'] : self::APP_PUBLIC_KEY;
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
     * Le jeton doit être chiffré par CryptService : c'est lui qui le déchiffre
     * côté validation (CaptchaTokenValidator, RecaptchaManager). Chiffrer ici
     * avec APP_SECRET_KEY/APP_PUBLIC_KEY donnait une clé et un IV différents
     * de ceux du service, donc un déchiffrement toujours en échec et un
     * captcha systématiquement refusé.
     *
     * @return string
     */
    private function generateToken(): string
    {
        return (string) $this->cryptService->execute($this->secretKey, 'e');
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
