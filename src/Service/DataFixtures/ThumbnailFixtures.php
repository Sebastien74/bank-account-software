<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\Website;
use App\Entity\Layout as LayoutEntities;
use App\Entity\Media as MediaEntities;
use App\Entity\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * ThumbnailFixtures.
 *
 * Thumbnail Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => ThumbnailFixtures::class, 'key' => 'thumbnail_fixtures'],
])]
class ThumbnailFixtures
{
    private ?User $user;
    private Website $website;
    private int $position = 1;

    /**
     * ThumbnailFixtures constructor.
     */
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Add ThumbnailFixtures.
     */
    public function add(Website $website, ?User $user = null, ?Website $websiteToDuplicate = null): void
    {
        $this->user = $user;
        $this->website = $website;
        if ($websiteToDuplicate instanceof Website) {
            $this->addDbThumbs($websiteToDuplicate);
        } else {
            $this->addThumbs();
        }
    }

    /**
     * To add DB ThumbConfiguration.
     */
    private function addDbThumbs(Website $websiteToDuplicate): void
    {
        $thumbs = $this->entityManager->getRepository(MediaEntities\ThumbConfiguration::class)->findBy(['configuration' => $websiteToDuplicate->getConfiguration()]);
        foreach ($thumbs as $referThumb) {
            $configuration = new MediaEntities\ThumbConfiguration();
            $configuration->setAdminName($referThumb->getAdminName());
            $configuration->setWidth($referThumb->getWidth());
            $configuration->setHeight($referThumb->getHeight());
            $configuration->setConfiguration($this->website->getConfiguration());
            $configuration->setPosition($referThumb->getPosition());
            $configuration->setScreen($referThumb->getScreen());
            $configuration->setCreatedBy($this->user);
            foreach ($referThumb->getActions() as $referAction) {
                $action = new MediaEntities\ThumbAction();
                $action->setAdminName($referAction->getAdminName());
                $action->setNamespace($referAction->getNamespace());
                $action->setAction($referAction->getAction());
                $action->setCreatedBy($this->user);
                $action->setBlockType($referAction->getBlockType());
                if ($referAction->getActionFilter() && !is_numeric($referAction->getActionFilter())) {
                    $action->setActionFilter($referAction->getActionFilter());
                }
                $configuration->addAction($action);
            }
            $this->entityManager->persist($configuration);
        }
    }

    /**
     * To add ThumbConfiguration.
     */
    private function addThumbs(): void
    {
        $headerTitle = $this->entityManager->getRepository(LayoutEntities\BlockType::class)->findOneBy(['slug' => 'title-header']);
        $this->addConfig('Thumbnail 1920 x 300', 1920, 300, 'Block entête', LayoutEntities\Block::class, 'block', $headerTitle);
        $this->addConfig('Thumbnail 991 x 300', 991, 300, 'Block entête', LayoutEntities\Block::class, 'block', $headerTitle, 'tablet');
        $this->addConfig('Thumbnail 412 x 350', 412, 350, 'Block entête', LayoutEntities\Block::class, 'block', $headerTitle, 'mobile');
        $this->addConfig('Thumbnail 1920 x 650', 1920, 650, 'Block entête large', LayoutEntities\Block::class, 'block', $headerTitle, 'large');
        $this->addConfig('Infinite');
    }

    /**
     * Add configuration.
     */
    private function addConfig(
        string $thumbConfigName,
        ?int $width = null,
        ?int $height = null,
        ?string $thumbActionName = null,
        ?string $classname = null,
        ?string $actionName = null,
        mixed $filter = null,
        string $screen = 'desktop',
        bool $fixedHeight = false): void
    {
        $configurations = $this->entityManager->getRepository(MediaEntities\ThumbConfiguration::class)->findBy([
            'configuration' => $this->website->getConfiguration(),
            'width' => $width,
            'height' => $height,
            'screen' => $screen,
        ]);

        if (!empty($configurations[0])) {
            $configuration = $configurations[0];
        } else {
            $configuration = new MediaEntities\ThumbConfiguration();
            $configuration->setAdminName($thumbConfigName);
            $configuration->setWidth($width);
            $configuration->setHeight($height);
            $configuration->setFixedHeight($fixedHeight);
            $configuration->setConfiguration($this->website->getConfiguration());
            $configuration->setPosition($this->position);
            $configuration->setCreatedBy($this->user);
            if ('large' !== $screen) {
                $configuration->setScreen($screen);
            }
            ++$this->position;
        }

        if ('Infinite' === $thumbConfigName) {
            $this->addThumbConfiguration($configuration, 'Page', LayoutEntities\Page::class);
            $blockMedia = $this->entityManager->getRepository(LayoutEntities\BlockType::class)->findOneBy(['slug' => 'media']);
            $this->addThumbConfiguration($configuration, 'Bloc média', LayoutEntities\Block::class, 'block', $blockMedia);
            $blockCard = $this->entityManager->getRepository(LayoutEntities\BlockType::class)->findOneBy(['slug' => 'card']);
            $this->addThumbConfiguration($configuration, 'Bloc mini fiche', LayoutEntities\Block::class, 'block', $blockCard);
        } else {
            $this->addThumbConfiguration($configuration, $thumbActionName, $classname, $actionName, $filter, $screen);
        }

        $this->entityManager->persist($configuration);
    }

    /**
     * Add ThumbConfiguration.
     */
    private function addThumbConfiguration(
        MediaEntities\ThumbConfiguration $configuration,
        ?string $thumbActionName = null,
        ?string $classname = null,
        ?string $actionName = null,
        mixed $filter = null,
        ?string $screen = null
    ): void {
        $action = new MediaEntities\ThumbAction();
        $action->setAdminName($thumbActionName);
        $action->setNamespace($classname);
        $action->setAction($actionName);
        $action->setCreatedBy($this->user);
        if (LayoutEntities\Block::class === $classname) {
            $action->setBlockType($filter);
        } else {
            $action->setActionFilter(strval($filter));
        }
        if ('large' === $screen) {
            $action->setActionFilter($screen);
        }
        $configuration->addAction($action);
    }
}
