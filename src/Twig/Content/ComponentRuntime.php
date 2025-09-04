<?php

declare(strict_types=1);

namespace App\Twig\Content;

use App\Entity\Layout\Action;
use App\Entity\Layout\Block;
use App\Entity\Layout\BlockIntl;
use App\Entity\Layout\BlockType;
use App\Entity\Layout\Col;
use App\Entity\Layout\Zone;
use App\Entity\Media\Media;
use App\Entity\Module\Slider\Slider;
use App\Entity\Security\User;
use App\Model\Core\WebsiteModel;
use App\Model\Layout\BlockModel;
use App\Model\MediasModel;
use App\Service\Interface\CoreLocatorInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\NonUniqueResultException;
use Faker\Factory;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\RuntimeExtensionInterface;

/**
 * ComponentRuntime.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class ComponentRuntime implements RuntimeExtensionInterface
{
    /**
     * ComponentRuntime constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly Environment $templating,
    )
    {
    }

    /**
     * Check if is Admin User.
     */
    public function isComponentUser(): bool
    {
        $tokenStorage = $this->coreLocator->tokenStorage();
        $currentUser = method_exists($tokenStorage, 'getToken') && $tokenStorage->getToken()
            ? $tokenStorage->getToken()->getUser() : null;

        return $currentUser instanceof User;
    }

    /**
     * Generate & render Zone.
     *
     * @throws MappingException|NonUniqueResultException
     */
    public function newZone(WebsiteModel $website, array $options = []): void
    {
        $template = $website->configuration->template;
        $zone = $this->addZone($options);

        if (!empty($options['blocks'])) {
            foreach ($options['blocks'] as $configuration) {
                foreach ($configuration as $blockTemplate => $blockOptions) {
                    try {
                        $this->newBlock($website, $blockTemplate, $blockOptions, $zone);
                    } catch (LoaderError|SyntaxError|RuntimeError $exception) {
                        dd($exception);
                    }
                }
            }
        }

        try {
            echo $this->templating->render('front/' . $template . '/include/zone.html.twig', [
                'disabledEditTools' => true,
                'forceContainer' => !$zone->isFullSize(),
                'transitions' => [],
                'seo' => $options['seo'],
                'website' => $website,
                'zone' => $zone,
            ]);
        } catch (LoaderError|RuntimeError|SyntaxError $exception) {
            //                        dd($exception);
        }
    }

    /**
     * Generate & render block.
     *
     * @throws LoaderError|RuntimeError|SyntaxError|MappingException|NonUniqueResultException
     */
    public function newBlock(WebsiteModel $website, string $blockTemplate, array $options = [], ?Zone $zone = null): void
    {
        $block = $this->addBlock($website, $blockTemplate, $options);
        $col = $this->addCol($block['block'], $blockTemplate, $options);
        $newZone = !$zone ? $this->addZone() : $zone;
        $newZone->addCol($col);
        $newZone->setCustomClass($blockTemplate);
        $template = $website->configuration->template;

        if ('title-header' === $blockTemplate) {
            $newZone->setPaddingTop(null);
            $newZone->setPaddingBottom(null);
            $newZone->setPaddingLeft('ps-0');
            $newZone->setPaddingRight('pe-0');
        }

        if ('title' === $blockTemplate) {
            $block['block']->block->setMarginBottom('mb-sm');
        }

        $arguments = array_merge([
            'template' => $template,
            'websiteTemplate' => $template,
            'thumbConfiguration' => [],
            'block' => $block['block'],
            'intl' => $block['block']->intl,
            'website' => $website,
        ], $options);

        if ('core-action' === $blockTemplate) {
            $newZone->setCustomClass('d-none');
            $arguments[$options['module']] = $block['entity'];
            if ('slider' === $options['module']) {
                $arguments['medias'] = MediasModel::fromEntity($block['entity'], $this->coreLocator)->list;
            }
            echo $this->templating->render('front/' . $template . '/actions/' . $options['module'] . '/' . $options['template'] . '.html.twig', $arguments);
        }

        if (!$zone) {
            echo $this->templating->render('front/' . $template . '/blocks/' . $blockTemplate . '/default.html.twig', $arguments);
        }
    }

    /**
     * Add Zone.
     */
    private function addZone(array $options = []): Zone
    {
        $fullSize = $options['fullSize'] ?? false;
        $position = $options['position'] ?? 1;
        $background = $options['background'] ?? null;

        $zone = new Zone();
        $zone->setFullSize($fullSize);
        $zone->setPosition($position);
        $zone->setBackgroundColor($background);
        $zone->setPaddingTop('pt-lg');
        $zone->setPaddingBottom('pb-lg');

        return $zone;
    }

    /**
     * Add Col.
     */
    private function addCol(BlockModel $block, string $blockTemplate, array $options = []): Col
    {
        $col = new Col();
        $col->addBlock($block->block);
        $module = $options['module'] ?? null;
        if ('media' === $blockTemplate || 'slider' === $module) {
            $col->setSize(6);
        }
        if ('title-header' === $blockTemplate) {
            $col->setPaddingLeft('ps-0');
            $col->setPaddingRight('pe-0');
        }

        return $col;
    }

    /**
     * Add Block.
     *
     * @throws MappingException|NonUniqueResultException
     */
    private function addBlock(WebsiteModel $website, string $blockTemplate, array $options = []): array
    {
        $titleForce = $options['titleForce'] ?? 2;
        $color = $options['color'] ?? null;

        /** @var BlockType $blockType */
        $blockType = $this->coreLocator->em()->getRepository(BlockType::class)->findOneBy(['slug' => $blockTemplate]);
        $intl = $this->addIntl(BlockIntl::class, $titleForce);

        $block = new Block();
        $block->setColor($color);
        $block->addIntl($intl);
        $block->setBlockType($blockType);
        $block->setAdminName('force-intl');
        $block->setPaddingLeft('ps-0');
        $block->setPaddingRight('pe-0');

        if ('media' === $blockTemplate) {
            $this->addMedias($website, $block, 1, $options);
        } elseif ('core-action' === $blockTemplate) {
            $action = $this->coreLocator->em()->getRepository(Action::class)->findOneBy(['slug' => $options['action']]);
            $block->setAction($action);
            $entity = null;
            if ('slider-view' === $options['action']) {
                $entity = $this->addSlider($website);
            }
            $this->addMedias($website, $entity, 5);
        }

        return [
            'block' => BlockModel::fromEntity($block, $this->coreLocator),
            'entity' => !empty($entity) ? $entity : null,
        ];
    }

    /**
     * Add Slider.
     */
    private function addSlider(WebsiteModel $website): Slider
    {
        $faker = Factory::create();
        $slider = new Slider();
        $slider->setAdminName('Components carrousel');
        $slider->setWebsite($website->entity);
        $slider->setSlug('components-carrousel' . $faker->slug());
        $slider->setIndicators(true);
        $slider->setPopup(true);

        return $slider;
    }

    /**
     * Add intl.
     */
    private function addIntl(string $intlClassname, int $titleForce = 2): mixed
    {
        $faker = Factory::create();

        $body = $faker->text(600);
        $body .= '<br><br><strong><span class="text-underline">Strong text</span> ' . $faker->text(10) . '</strong>';
        $body .= '<br><b><span class="text-underline">Bold text</span> ' . $faker->text(10) . '</b>';
        $body .= '<br><small><span class="text-underline">Small text</span> ' . $faker->text(10) . '</small>';
        $body .= '<br><a href="' . $this->coreLocator->request()->getSchemeAndHttpHost() . '">Link : ' . $faker->text(10) . '</a>';
        $body .= '<br><br><ul><li>' . $faker->text(10) . '</li><li>' . $faker->text(10) . '</li><li>' . $faker->text(10) . '</li></ul>';

        $intl = new $intlClassname();
        $intl->setTitleForce($titleForce);
        $intl->setLocale($this->coreLocator->request()->getLocale());
        $intl->setTitle('H' . $titleForce . '. ' . $faker->text(35));
        $intl->setBody($body);
        $intl->setIntroduction($faker->text(600));
        $intl->setSubTitle('Sous-titre ' . $faker->text(15));
        $intl->setTargetLink($this->coreLocator->request()->getSchemeAndHttpHost());
        $intl->setTargetLabel($faker->text(10));

        return $intl;
    }

    /**
     * Add Media[].
     *
     * @throws MappingException
     */
    private function addMedias(WebsiteModel $website, mixed $entity, int $count, array $options = []): void
    {
        $faker = Factory::create();
        $type = $options['type'] ?? 'image';

        for ($i = 1; $i <= $count; ++$i) {
            $filename = 'image' === $type ? 'image-' . $i . '.jpg' : 'file.pdf';
            $mediaRelationData = $this->coreLocator->metadata($entity, 'mediaRelations');
            $mediaRelation = new ($mediaRelationData->targetEntity)();
            $intlData = $this->coreLocator->metadata($mediaRelation, 'intl');
            $intl = new ($intlData->targetEntity)();
            $title = 'image' === $type ? 'Titre de votre image' : 'Titre de votre fichier';
            $targetLabel = 'image' === $type ? $intl->getTargetLabel() : 'Télécharger';

            $intl->setTitle($title);
            $intl->setTargetLabel($targetLabel);

            $media = new Media();
            $media->setWebsite($website->entity);
            $media->setCategory('cms-component');
            $media->setName($filename);
            $media->setFilename('/medias/components/' . $filename);
            $media->setCopyright($faker->company);
            $media->setExtension('jpg');
            $media->setNotContractual(true);

            $mediaRelation->setMedia($media);
            $mediaRelation->setLocale($this->coreLocator->request()->getLocale());
            $mediaRelation->setCategorySlug('cms-component');
            $mediaRelation->setPopup(true);
            $mediaRelation->setDownloadable(true);
            $mediaRelation->setPosition($i);
            $mediaRelation->setIntl($intl);

            $entity->addMediaRelation($mediaRelation);
        }
    }
}
