<?php

declare(strict_types=1);

namespace App\Model\Layout;

use App\Entity\Layout\Block;
use App\Entity\Module\Catalog\Product;
use App\Entity\Module\Newscast\Newscast;
use App\Model\BaseModel;
use App\Model\EntityModel;
use App\Model\IntlModel;
use App\Model\MediaModel;
use App\Model\Module\NewscastModel;
use App\Model\Module\ProductModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;

/**
 * BlockModel.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
final class BlockModel extends BaseModel
{
    /**
     * BlockModel constructor.
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?Block $block = null,
        public readonly ?object $intl = null,
        public readonly ?object $media = null,
        public readonly ?bool $haveContent = null,
        public readonly ?bool $haveMedia = null,
        public readonly ?string $slug = null,
        public readonly ?string $style = null,
        public readonly ?string $color = null,
        public readonly ?string $fontSize = null,
        public readonly ?string $fontWeight = null,
        public readonly ?string $fontWeightSecondary = null,
        public readonly ?string $fontFamily = null,
        public readonly ?string $backgroundColor = null,
        public readonly ?string $icon = null,
        public readonly ?string $iconSize = null,
        public readonly ?string $iconPosition = null,
        public readonly ?string $script = null,
    ) {
    }

    /**
     * @throws NonUniqueResultException|MappingException
     */
    public static function fromEntity(Block $block, CoreLocatorInterface $coreLocator): self
    {
        self::setLocators($coreLocator);

        $slug = self::getContent('slug', $block->getBlockType());
        $mediaBlocks = ['media'];
        $mediaIntlBlocks = ['card', 'title-header', 'video', 'modal'];
        $getMediaAndIntl = in_array($slug, $mediaIntlBlocks);
        $getMedia = in_array($slug, $mediaBlocks) || $getMediaAndIntl;
        $getIntl = !$getMedia || $getMediaAndIntl;
        $intl = $getIntl ? IntlModel::fromEntity($block, $coreLocator, false) : null;
        $intl = self::intlForm($slug, $intl);
        $media = $getMedia ? MediaModel::fromEntity($block, $coreLocator, false) : null;
        $color = self::getContent('color', $block);
        $fontSize = self::getContent('fontSize', $block);
        $fontWeight = self::getContent('fontWeight', $block);
        $fontWeightSecondary = self::getContent('fontWeightSecondary', $block);
        $fontFamily = self::getContent('fontFamily', $block);

        return new self(
            id: $block->getId(),
            block: $block,
            intl: $intl,
            media: $media,
            haveContent: $getIntl ? $intl->haveContent : false,
            haveMedia: $media instanceof MediaModel ? $media->haveMedia : false,
            slug: $slug,
            style: self::styleClass($block),
            color: $color ? 'text-'.$color : null,
            fontSize: $fontSize ? 'fz-'.$fontSize : null,
            fontWeight: $fontWeight ? 'fw-'.$fontWeight : null,
            fontWeightSecondary: $fontWeightSecondary ? 'fw-'.$fontWeightSecondary : null,
            fontFamily: $fontFamily ? 'ff-'.$fontFamily : null,
            backgroundColor: self::getContent('backgroundColor', $block),
            icon: self::getContent('icon', $block),
            iconSize: self::getContent('iconSize', $block),
            iconPosition: self::getContent('iconPosition', $block),
            script: self::getContent('script', $block),
        );
    }

    /**
     * To set title by entity URL parameters.
     *
     * @throws NonUniqueResultException|MappingException
     */
    private static function intlForm(?string $slug = null, ?IntlModel $intl = null): ?object
    {
        if ($intl && 'title-header' === $slug && self::$coreLocator->request()->get('category') && self::$coreLocator->request()->get('code')) {
            $interface = self::$coreLocator->interfaceHelper()->interfaceByName(self::$coreLocator->request()->get('category'));
            $entity = !empty($interface['classname']) ? self::$coreLocator->em()->getRepository($interface['classname'])->find(self::$coreLocator->request()->get('code')) : null;
            $modelClassnames = [
                Product::class => ProductModel::class,
                Newscast::class => NewscastModel::class,
            ];
            $modelClassname = $entity && !empty($modelClassnames[get_class($entity)]) ? $modelClassnames[get_class($entity)] : EntityModel::class;
            $model = $entity ? ($modelClassname)::fromEntity($entity, self::$coreLocator, [
                'disabledUrl' => true,
                'disabledMedias' => true,
                'disabledLayout' => true,
                'disabledCategories' => true,
                'disabledCategory' => true,
            ]) : null;
            $intl = $entity ? (array) $intl : $intl;
            if ($model && is_array($intl)) {
                $model = $model instanceof EntityModel ? $model->response : $model;
                $intl['title'] = $model->intl->title;
                $intl = (object) $intl;
            }
        }

        return $intl;
    }
}
