<?php

declare(strict_types=1);

namespace App\Form\Manager\Layout;

use App\Entity\Core\Website;
use App\Entity\Layout;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Form\Form;

/**
 * BlockManager.
 *
 * Manage admin Block form
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => BlockManager::class, 'key' => 'layout_block_form_manager'],
])]
class BlockManager
{
    private string $blockType;

    /**
     * BlockManager constructor.
     */
    public function __construct(private readonly CoreLocatorInterface $coreLocator)
    {
    }

    /**
     * @preUpdate
     *
     * @throws NonUniqueResultException|MappingException
     */
    public function preUpdate(Layout\Block $block, Website $website, array $interface, Form $form): void
    {
        $blockTypeSlug = $block->getBlockType() instanceof Layout\BlockType ? $block->getBlockType()->getSlug() : null;
        $setter = 'set'.ucfirst(str_replace('-', '', $blockTypeSlug));
        if (method_exists($this, $setter)) {
            $this->$setter($block, $website);
        }
        $this->setListing($block);

        if ('card' === $blockTypeSlug) {
            foreach ($block->getIntls() as $intl) {
                if (!$intl->getTitleForce()) {
                    $intl->setTitleForce(3);
                }
            }
        }
    }

    /**
     * To set Block Media.
     */
    public function setMedias(Layout\Block $block, Website $website): void
    {
        $configuration = $website->getConfiguration();
        $mediaRelations = $block->getMediaRelations();
        if ($configuration->isMediasSecondary()) {
            $existing = [];
            foreach ($mediaRelations as $mediaRelation) {
                $existing[$mediaRelation->getLocale()][$mediaRelation->getPosition()] = true;
            }
            for ($i = 1; $i <= 2; ++$i) {
                foreach ($configuration->getAllLocales() as $locale) {
                    if (empty($existing[$locale][$i])) {
                        $mediaRelation = new Layout\BlockMediaRelation();
                        $mediaRelation->setLocale($locale);
                        $mediaRelation->setPosition($i);
                        $block->addMediaRelation($mediaRelation);
                    }
                }
            }
        }
    }

    /**
     * To set Page in Listing.
     */
    private function setListing(Layout\Block $block): void
    {
        $action = $block->getAction();
        if ($action instanceof Layout\Action) {
            $entitiesToSet = ['Listing'];
            $classname = $action->getEntity();
            if ($classname) {
                $referEntity = new $classname();
                $matches = $action->getEntity() ? explode('\\', $classname) : [];
                $entityName = end($matches);
                if (in_array($entityName, $entitiesToSet) && method_exists($referEntity, 'setPage')) {
                    $layout = $block->getCol()->getZone()->getLayout();
                    $page = $this->coreLocator->em()->getRepository(Layout\Page::class)->findOneBy(['layout' => $layout]);
                    foreach ($block->getActionIntls() as $actionIntl) {
                        if ($actionIntl->getActionFilter()) {
                            $listing = $this->coreLocator->em()->getRepository($classname)->find($actionIntl->getActionFilter());
                            $listing->setPage($page);
                            $this->coreLocator->em()->persist($listing);
                        }
                    }
                }
            }
        }
    }

    /**
     * To get Medias for tabs form.
     */
    public function getMediaRelationsTabs(Layout\Block $block, Website $website): array
    {
        $tabs = [];
        if ($website->getConfiguration()->isMediasSecondary()) {
            foreach ($block->getMediaRelations() as $mediaRelation) {
                $tabs[$mediaRelation->getLocale()][$mediaRelation->getPosition()] = $mediaRelation;
                ksort($tabs);
                ksort($tabs[$mediaRelation->getLocale()]);
            }
        }

        return $tabs;
    }
}
