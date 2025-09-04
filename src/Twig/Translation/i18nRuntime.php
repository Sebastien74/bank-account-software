<?php

declare(strict_types=1);

namespace App\Twig\Translation;

use App\Entity\Core\Domain;
use App\Entity\Core\Website;
use App\Model\ViewModel;
use App\Service\Interface\CoreLocatorInterface;
use App\Twig\Core\AppRuntime;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query\QueryException;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * i18nRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class i18nRuntime implements RuntimeExtensionInterface
{
    /**
     * i18nRuntime constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly AppRuntime $appExtension,
    ) {
    }

    /**
     * To set request.
     */
    private function setRequest(): void
    {
    }

    /**
     * Get intl by locale.
     *
     * @throws NonUniqueResultException
     */
    public function intl(mixed $entity = null, ?string $locale = null, ?bool $force = null): mixed
    {
        $this->setRequest();
        $locale = !$locale && $this->coreLocator->request() instanceof Request ? $this->coreLocator->request()->getLocale() : (!$locale ? 'fr' : $locale);
        if ($entity instanceof PersistentCollection) {
            foreach ($entity as $item) {
                $isObject = is_object($item);
                $isArray = is_array($item);
                $haveLocale = $isObject && method_exists($item, 'getLocale') || $isArray && !empty($item['locale']);
                if ($haveLocale) {
                    $intlLocale = $isObject ? $item->getLocale() : $item['locale'];
                    if ($intlLocale === $locale) {
                        return $item;
                    }
                }
            }

            return null;
        }

        $isObject = is_object($entity);
        $isArray = is_array($entity);
        $asIntls = $isObject && method_exists($entity, 'getIntls') || $isArray && !empty($entity['intls']);
        $haveAdminName = $isObject && method_exists($entity, 'getAdminName') || $isArray && !empty($entity['adminName']);
        $entityId = $isObject && method_exists($entity, 'getId') ? $entity->getId()
            : ($isObject && property_exists($entity, 'id') ? $entity->id : (!empty($entity['id']) ? $entity['id'] : null));
        $adminName = $isObject && $haveAdminName ? $entity->getAdminName() : ($isArray && $haveAdminName ? $entity['adminName'] : null);
        $intls = $isObject && method_exists($entity, 'getIntls') ? $entity->getIntls() : (is_array($entity) && !empty($entity['intls']) ? $entity['intls'] : []);

        if ($asIntls && $entityId > 0 || $asIntls && $haveAdminName && 'force-intl' === $adminName || $asIntls && $force) {
            foreach ($intls as $intl) {
                $intlLocale = $isObject ? $intl->getLocale() : $intl['locale'];
                if ($intlLocale === $locale) {
                    return $intl;
                }
            }
        } elseif (is_iterable($entity)) {
            foreach ($entity as $intl) {
                if (is_object($entity) && method_exists($intl, 'getLocale') && $intl->getLocale() === $locale
                    || is_array($intl) && !empty($intl['locale']) && $intl['locale'] === $locale) {
                    return $intl;
                }
            }
        } elseif ($isObject && method_exists($entity, 'getIntl')) {
            return $entity->getIntl();
        }

        return [];
    }

    /**
     * Get intl MediaRelation by locale.
     *
     * @throws NonUniqueResultException
     */
    public function intlMedia(object $entity, ?string $locale = null): mixed
    {
        $this->setRequest();
        $locale = !$locale && $this->coreLocator->request() instanceof Request ? $this->coreLocator->request()->getLocale() : (!$locale ? 'fr' : $locale);
        $isObject = is_object($entity);
        $mediaRelations = $isObject && method_exists($entity, 'getMediaRelations') ? $entity->getMediaRelations() : [];
        if ($mediaRelations) {
            foreach ($mediaRelations as $mediaRelation) {
                $localeMedia = $mediaRelation->getLocale();
                $media = $mediaRelation->getMedia();
                if ($media) {
                    $filename = $media->getFilename();
                    $mediaScreens = $media->getMediaScreens();
                    if ($localeMedia === $locale && $filename) {
                        return $mediaRelation;
                    } elseif ($localeMedia === $locale && count($mediaScreens) > 0) {
                        foreach ($mediaScreens as $mediaScreen) {
                            $filename = $mediaScreen->getFilename();
                            if ($filename) {
                                return $mediaScreen;
                            }
                        }

                        return $mediaRelation;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Get intl MediaRelation by locale.
     */
    public function intlMedias($entity, ?string $locale = null): array
    {
        $this->setRequest();
        $locale = !$locale ? $this->coreLocator->request()->getLocale() : $locale;
        $medias = [];
        if (is_object($entity) && method_exists($entity, 'getMediaRelations')) {
            foreach ($entity->getMediaRelations() as $intl) {
                if ($intl->getLocale() === $locale) {
                    $medias[] = $intl;
                }
            }
        }

        return $medias;
    }

    /**
     * Find intl by classname and id.
     */
    public function findIntl(string $classname, ?int $id): mixed
    {
        $entity = $this->appExtension->find($classname, $id);
        try {
            return $this->intl($entity);
        } catch (NonUniqueResultException $e) {
        }

        return [];
    }

    /**
     * Find intl[] Block Types.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    public function intlsWithContent(string $classname, mixed $masterEntity = null): array
    {
        $this->setRequest();
        $statement = $this->coreLocator->em()->getRepository($classname)
            ->createQueryBuilder('e')
            ->leftJoin('e.intls', 'i')
            ->andWhere('i.locale = :locale')
            ->andWhere('i.title IS NOT NULL OR i.body IS NOT NULL')
            ->setParameter('locale', $this->coreLocator->request()->getLocale())
            ->addSelect('i');

        if ($masterEntity) {
            $interface = $this->coreLocator->interfaceHelper()->generate($classname);
            $getter = !empty($interface['masterField']) ? 'get'.ucfirst($interface['masterField']) : false;
            $referClass = new $classname();
            if (is_object($referClass) && $getter && method_exists($referClass, $getter)) {
                $statement->andWhere('e.'.$interface['masterField'].' = :masterEntity')
                    ->setParameter('masterEntity', $masterEntity);
            }
        }

        $result = $statement->getQuery()->getResult();

        $entitiesWithMedia = [];
        foreach ($result as $entity) {
            $entitiesWithMedia[$entity->getId()] = ViewModel::fromEntity($entity, $this->coreLocator)->intl;
        }

        return $entitiesWithMedia;
    }

    /**
     * Get target domain.
     */
    private function getTargetDomain(Website $website): ?string
    {
        $this->setRequest();
        foreach ($website->getConfiguration()->getDomains() as $domain) {
            /** @var Domain $domain */
            $sameDomain = $domain->getName() === $this->coreLocator->request()->getHost();
            if ($sameDomain && $domain->getLocale() === $this->coreLocator->request()->getLocale() && $domain->isAsDefault()) {
                $protocol = $this->coreLocator->request()->isSecure() ? 'https' : 'http';

                return $protocol.'://'.$domain->getName();
            }
        }

        return null;
    }

    /**
     * Get Entity value.
     */
    private function getValue(mixed $entity, string $property): mixed
    {
        if (is_object($entity)) {
            $getter = method_exists($entity, 'get'.ucfirst($property)) ? 'get'.ucfirst($property) : 'is'.ucfirst($property);
            if (method_exists($entity, $getter)) {
                return $entity->$getter();
            }

            return null;
        }

        return !isset($entity[$property]) ? null : $entity[$property];
    }
}
