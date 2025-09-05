<?php

declare(strict_types=1);

namespace App\Model;

use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Query\QueryException;
use Exception;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

/**
 * ViewModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class ViewModel extends BaseModel
{
    private static array $cache = [];

    /**
     * ViewModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $slug = null,
        public readonly ?string $adminName = null,
        public readonly mixed $entity = null,
        public readonly ?string $route = null,
        public readonly ?array $interface = null,
        public readonly ?string $interfaceName = null,
        public readonly mixed $category = null,
        public readonly ?string $categorySlug = null,
        public readonly ?iterable $categories = null,
        public readonly ?IntlModel $intl = null,
        public readonly ?object $intlCard = null,
        public readonly ?bool $haveContent = null,
        public readonly ?bool $pastDate = null,
        public readonly ?\DateTime $date = null,
        public readonly array $dates = [],
        public readonly ?string $formatDate = null,
        public readonly ?string $author = null,
        public readonly ?array $medias = [],
        public readonly ?object $mainMedia = null,
        public readonly ?array $mediasWithoutMain = null,
        public readonly bool $haveMedias = false,
        public readonly bool $haveMainMedia = false,
        public readonly ?object $headerMedia = null,
        public readonly ?array $videos = null,
        public readonly bool $haveVideos = false,
        public readonly object|bool $mainVideo = false,
        public readonly ?array $files = null,
        public readonly bool $haveFiles = false,
        public readonly ?object $mainFile = null,
        public readonly ?bool $showGdprVideoBtn = null,
        public readonly ?array $mediasAndVideos = null,
        public readonly ?bool $mainMediaInHeader = null,
        public readonly ?bool $haveLayout = null,
        public readonly ?bool $haveStickyCol = null,
        public readonly ?bool $asCustomLayout = null,
        public readonly ?bool $showImage = null,
        public readonly ?bool $showTitle = null,
        public readonly ?bool $showSubTitle = null,
        public readonly ?bool $showCategory = null,
        public readonly ?bool $showIntro = null,
        public readonly ?bool $showBody = null,
        public readonly ?bool $showDate = null,
        public readonly ?bool $showLinkCard = null,
        public readonly ?int $position = null,
        public readonly ?string $template = null,
        public readonly ?array $preloadFiles = [],
    ) {
    }

    /**
     * fromEntity.
     *
     * @throws NonUniqueResultException|MappingException|QueryException|Exception
     */
    public static function fromEntity(mixed $entity, CoreLocatorInterface $coreLocator, array $options = []): self
    {
        $entity = $entity && property_exists($entity, 'entity') && !method_exists($entity, 'getEntity') ? $entity->entity : $entity;
        $entitiesIds = !empty($options['entitiesIds']) ? $options['entitiesIds'] : [];

        self::setLocators($coreLocator);
        if ($entity) {
            self::$coreLocator->interfaceHelper()->setInterface(get_class($entity));
        }

        $disabledLayout = isset($options['disabledLayout']) && $options['disabledLayout'];
        $disabledIntl = isset($options['disabledIntl']) && $options['disabledIntl'];
        $disabledMedias = isset($options['disabledMedias']) && $options['disabledMedias'];
        $disabledUrl = isset($options['disabledUrl']) && $options['disabledUrl'];
        $interface = $coreLocator->interfaceHelper()->getInterface();
        $configEntity = !empty($options['configEntity']) ? $options['configEntity'] : null;
        $configFields = self::getContent('fields', $configEntity, false, true);
        $locale = !empty($options['locale']) ? $options['locale'] : self::$coreLocator->locale();
        $intl = $entitiesIds ? IntlModel::fromEntities($entity, $coreLocator, $options['entitiesIds']) : [];
        $intl = $intl ?: (!$disabledIntl ? IntlModel::fromEntity($entity, $coreLocator, false, $options) : null);
        $intlVideo = !$disabledIntl && $intl->video ? (object) ['type' => 'video', 'videoLink' => $intl->video, 'path' => $intl->video, 'locale' => $intl->locale] : null;
        $haveIntlVideo = !empty($intlVideo);
        $medias = $entitiesIds ? MediasModel::fromEntities($entity, $coreLocator, $options['entitiesIds']) : [];
        $medias = $medias ?: (!$disabledMedias ? MediasModel::fromEntity($entity, $coreLocator, $locale, false, $options) : null);
        $mediasAndVideos = !$disabledMedias && $medias->mediasAndVideos ? $medias->mediasAndVideos : [];
        $categories = !isset($options['disabledCategories']) ? self::getContent('categories', $entity, false, true, true) : [];
        $category = !isset($options['disabledCategory']) ? self::category($entity) : null;
        $category = $category ?: (!empty($categories) ? $categories[0] : null);
        $date = self::date($entity);
        $dates = self::dates($entity);
        $haveVideos = (!$disabledMedias && !empty($medias->videos)) || $haveIntlVideo;

        return new self(
//            id: self::getContent('id', $entity),
//            slug: self::getContent('slug', $entity),
//            adminName: self::getContent('adminName', $entity),
//            entity: $entity,
//            route: self::$coreLocator->request()->get('_route'),
//            interface: $interface,
//            interfaceName: !empty($interface['name']) ? $interface['name'] : null,
//            category: $category,
//            categorySlug: $category ? self::getContent('slug', $category) : null,
//            categories: $categories,
//            intl: $intl,
//            haveContent: $intl && ($intl->body || $intl->introduction),
//            pastDate: $dates['startDate'] && $dates['startDate'] <= new \DateTime('now', new \DateTimeZone('Europe/Paris')),
//            date: $date,
//            dates: $dates,
//            formatDate: self::getContent('formatDate', $entity),
//            author: self::getContent('author', $entity),
//            medias: !$disabledMedias ? $medias->list : [],
//            mainMedia: !$disabledMedias ? $medias->main : null,
//            mediasWithoutMain: !$disabledMedias ? $medias->withoutMain : [],
//            haveMedias: !$disabledMedias ? $medias->haveMedias : false,
//            haveMainMedia: !$disabledMedias ? $medias->haveMain : false,
//            headerMedia: !$disabledMedias ? $medias->header : null,
//            videos: !$disabledMedias && $medias->videos && !$intlVideo ? $medias->videos : ($intlVideo && !$disabledMedias && $medias->videos ? array_merge([$intlVideo], $medias->videos) : ($haveIntlVideo ? [$intlVideo] : [])),
//            haveVideos: $haveVideos,
//            mainVideo: !$disabledMedias && $medias->mainVideo ? $medias->mainVideo : ($haveIntlVideo ? $intlVideo : false),
//            files: !$disabledMedias && $medias->files ? $medias->files : [],
//            haveFiles: !$disabledMedias ? $medias->haveFiles : false,
//            mainFile: !$disabledMedias && $medias->mainFile ? $medias->mainFile : (object) [],
//            mediasAndVideos: $mediasAndVideos && !$intlVideo ? $mediasAndVideos : ($intlVideo && $mediasAndVideos ? array_merge([$intlVideo], $mediasAndVideos) : ($intlVideo ? [$intlVideo] : [])),
//            showImage: in_array('image', $configFields),
//            showTitle: in_array('title', $configFields),
//            showSubTitle: in_array('sub-title', $configFields),
//            showCategory: in_array('category', $configFields),
//            showIntro: in_array('introduction', $configFields),
//            showBody: in_array('body', $configFields),
//            showDate: in_array('date', $configFields),
//            showLinkCard: in_array('card-link', $configFields),
//            position: self::getContent('position', $entity),
//            template: self::getContent('template', $entity),
        );
    }

    /**
     * Get category.
     *
     * @throws NonUniqueResultException|MappingException|QueryException
     */
    private static function category(mixed $entity = null): ?object
    {
        $stop = !empty($options['stop']) || !is_object($entity);
        if ($stop) {
            return null;
        }

        $category = self::getContent('category', $entity);
        $category = !$category ? self::getContent('mainCategory', $entity) : $category;

        if ($category) {
            if (isset(self::$cache['category'][get_class($category)][$category->getId()])) {
                return self::$cache['category'][get_class($category)][$category->getId()];
            }
            $qb = self::$coreLocator->em()->getRepository(get_class($category))
                ->createQueryBuilder('c')
                ->andWhere('c.id =  :id')
                ->setParameter('id', $category->getId());
            if (method_exists($category, 'getIntls')) {
                $qb->leftJoin('c.intls', 'i')
                    ->addSelect('i');
            }
            if (method_exists($category, 'getMediaRelations')) {
                $qb->leftJoin('c.mediaRelations', 'mr')
                    ->addSelect('mr');
            }
            $category = $qb->getQuery()->getOneOrNullResult();
            if (is_object($category)) {
                self::$cache['category'][get_class($category)][$category->getId()] = ViewModel::fromEntity($category, self::$coreLocator, ['stop' => true]);

                return self::$cache['category'][get_class($category)][$category->getId()];
            }
        }

        return $category;
    }

    /**
     * To get date.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private static function date(mixed $entity): ?object
    {
        $publicationDate = self::getContent('publicationDate', $entity);
        $publicationStart = self::getContent('publicationStart', $entity);
        $startDate = self::getContent('startDate', $entity);

        return $startDate ?: ($publicationDate ?: $publicationStart);
    }

    /**
     * To get dates.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private static function dates(mixed $entity): array
    {
        return [
            'publicationDate' => self::getContent('publicationDate', $entity),
            'publicationStart' => self::getContent('publicationStart', $entity),
            'publicationEnd' => self::getContent('publicationEnd', $entity),
            'startDate' => self::getContent('startDate', $entity),
            'endDate' => self::getContent('endDate', $entity),
        ];
    }
}
