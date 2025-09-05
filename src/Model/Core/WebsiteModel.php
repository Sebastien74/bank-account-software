<?php

declare(strict_types=1);

namespace App\Model\Core;

use App\Entity\Core\Domain;
use App\Entity\Core\Security;
use App\Entity\Core\Website;
use App\Entity\Core\Website as WebsiteEntity;
use App\Model\BaseModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

/**
 * WebsiteModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class WebsiteModel extends BaseModel
{
    private static array $cache = [];

    /**
     * WebsiteModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $slug = null,
        public readonly ?WebsiteEntity $entity = null,
        public readonly ?string $uploadDirname = null,
        public readonly ?ConfigurationModel $configuration = null,
        public readonly ?InformationModel $information = null,
        public readonly ?object $hosts = null,
        public readonly ?string $schemeAndHttpHost = null,
        public readonly ?Security $security = null,
        public readonly ?string $securityDashboardUrl = null,
        public readonly ?array $logos = null,
        public readonly ?string $logo = null,
        public readonly ?string $footerLogo = null,
        public readonly ?string $emailLogo = null,
        public readonly ?bool $isEmpty = null,
    ) {
    }

    /**
     * Get model.
     *
     * @throws MappingException|NonUniqueResultException|InvalidArgumentException
     */
    public static function fromEntity(WebsiteEntity $website, CoreLocatorInterface $coreLocator, ?string $locale = null): self
    {
        self::setLocators($coreLocator);

        $locale = $locale ?: self::$coreLocator->locale();

        if (isset(self::$cache['response'][$website->getId()][$locale])) {
            return self::$cache['response'][$website->getId()][$locale];
        }

        $information = InformationModel::fromEntity($website, $coreLocator, $locale);
        $configuration = ConfigurationModel::fromEntity(self::cache($website, 'configuration', self::$cache), $information, $coreLocator, $locale);
        $security = $website->getSecurity();

        /** Preload logo */
        $logo = !empty($information->logos['logo']) && $coreLocator->schemeAndHttpHost() && str_contains($information->logos['logo'], self::$coreLocator->schemeAndHttpHost())
            ? $information->logos['logo'] : (!empty($information->logos['logo']) ? self::$coreLocator->schemeAndHttpHost().$information->logos['logo'] : null);
        $linkProvider = self::$coreLocator->request() ? self::$coreLocator->request()->attributes->get('_links', new GenericLinkProvider()) : null;
        if ($logo && $linkProvider) {
            self::$coreLocator->request()->attributes->set('_links', $linkProvider->withLink(
                (new Link('preload', $logo))->withAttribute('as', 'image')
            ));
        }

        $hosts = self::host($website);

        self::$cache['response'][$website->getId()][$locale] = new self(
            id: $website->getId(),
            slug: $website->getSlug(),
            entity: $website,
            uploadDirname: $website->getUploadDirname(),
            configuration: $configuration,
            information: $information,
            hosts: $hosts,
            schemeAndHttpHost: $hosts->schemeAndHttpHost,
            security: $security,
            logos: $information->logos,
            logo: !empty($information->logos['logo']) ? $information->logos['logo'] : null,
            footerLogo: !empty($information->logos['footer']) ? $information->logos['footer'] : null,
            emailLogo: !empty($information->logos['email']) ? $information->logos['email'] : null,
            isEmpty: !$website->getId(),
        );

        return self::$cache['response'][$website->getId()][$locale];
    }

    /**
     * Get Hosts.
     */
    private static function host(?Website $website): object
    {
        $host = null;
        $schemeAndHttpHost = null;
        $request = self::$coreLocator->request();

        if (is_object($request) && method_exists($request, 'getHost')) {
            $host = $request->getHost();
            $schemeAndHttpHost = $request->getSchemeAndHttpHost();
        } elseif ($website instanceof Website) {
            $configuration = $website->getConfiguration();
            $defaultLocale = $configuration->getLocale();
            $domains = self::$coreLocator->em()->getRepository(Domain::class)->findBy(['configuration' => $configuration, 'locale' => $defaultLocale, 'asDefault' => true]);
            $host = !empty($domains) ? $domains[0]->getName() : null;
            $schemeAndHttpHost = 'https://'.$host;
        }

        return (object) [
            'host' => $host,
            'schemeAndHttpHost' => $schemeAndHttpHost,
        ];
    }
}
