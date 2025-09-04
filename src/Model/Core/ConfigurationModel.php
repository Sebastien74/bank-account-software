<?php

declare(strict_types=1);

namespace App\Model\Core;

use App\Entity\Core\Configuration;
use App\Model\BaseModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;

/**
 * ConfigurationModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class ConfigurationModel extends BaseModel
{
    private static array $cache = [];

    /**
     * ConfigurationModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?object $entity = null,
        public readonly ?string $locale = null,
        public readonly ?array $allLocales = null,
        public readonly ?array $onlineLocales = null,
        public readonly ?bool $asMultiLocales = null,
        public readonly ?string $template = null,
        public readonly ?bool $onlineStatus = null,
        public readonly ?array $ipsBan = null,
        public readonly ?array $ipsDev = null,
        public readonly ?array $ipsCustomer = null,
        public readonly ?array $domains = null,
        public readonly ?object $domain = null,
        public readonly ?array $medias = null,
        public readonly ?array $logos = null,
        public readonly ?string $adminTheme = null,
        public readonly ?string $buildTheme = null,
        public readonly ?string $charset = null,
    ) {
    }

    /**
     * Get model.
     *
     * @throws NonUniqueResultException|MappingException|InvalidArgumentException
     */
    public static function fromEntity(Configuration $configuration, InformationModel $informationModel, CoreLocatorInterface $coreLocator, ?string $locale = null): self
    {
        self::setLocators($coreLocator);

        $locale = $locale ?: self::$coreLocator->locale();

        if (isset(self::$cache['response'][$configuration->getId()][$locale])) {
            return self::$cache['response'][$configuration->getId()][$locale];
        }

        $domains = DomainModel::fromEntity($configuration, $coreLocator, $locale);
        $allLocales = self::getContent('allLocales', $configuration, false, true);

        self::$cache['response'][$configuration->getId()][$locale] = new self(
            id: self::getContent('id', $configuration),
            entity: $configuration,
            locale: self::getContent('locale', $configuration),
            allLocales: $allLocales,
            onlineLocales: self::getContent('onlineLocales', $configuration, false, true),
            asMultiLocales: count($allLocales) > 1,
            template: self::getContent('template', $configuration),
            onlineStatus: self::getContent('onlineStatus', $configuration, true),
            ipsBan: self::getContent('ipsBan', $configuration, false, true),
            ipsDev: self::getContent('ipsDev', $configuration, false, true),
            ipsCustomer: self::getContent('ipsCustomer', $configuration, false, true),
            domains: $domains->list,
            domain: $domains->default,
            medias: $informationModel->medias,
            logos: $informationModel->logos,
            adminTheme: self::getContent('adminTheme', $configuration),
            buildTheme: self::getContent('buildTheme', $configuration),
            charset: self::getContent('charset', $configuration),
        );

        return self::$cache['response'][$configuration->getId()][$locale];
    }
}
