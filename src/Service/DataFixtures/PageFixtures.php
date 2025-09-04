<?php

declare(strict_types=1);

namespace App\Service\DataFixtures;

use App\Entity\Core\Website;
use App\Entity\Layout;
use App\Entity\Security\User;
use App\Entity\Seo\Url;
use App\Form\Manager\Layout\LayoutManager;
use App\Service\Core\Urlizer;
use App\Service\Interface\CoreLocatorInterface;
use Exception;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * PageFixtures.
 *
 * Page Fixtures management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Autoconfigure(tags: [
    ['name' => PageFixtures::class, 'key' => 'page_fixtures'],
])]
class PageFixtures
{
    private Website $website;
    private ?User $user;
    private bool $flush;
    private string $locale = '';
    private array $pages = [];
    private int $layoutPosition = 1;

    /**
     * PageFixtures constructor.
     */
    public function __construct(
        private readonly CoreLocatorInterface $coreLocator,
        private readonly LayoutManager $layoutManager,
    ) {
    }

    /**
     * Add Pages.
     *
     * @throws Exception
     */
    public function add(Website $website, array $pagesParams, ?User $user = null, bool $flush = true, array $mainPages = []): array
    {
        $this->website = $website;
        $this->user = $user;
        $this->flush = $flush;
        $this->locale = $website->getConfiguration()->getLocale();
        $this->layoutPosition = count($this->coreLocator->em()->getRepository(Layout\Layout::class)->findBy(['website' => $this->website])) + 1;

        foreach ($pagesParams as $key => $pageParams) {
            $params = (object) $pageParams;
            $enable = !property_exists($params, 'disable') || false === $params->disable;
            if ($enable) {
                $existingPage = $this->coreLocator->em()->getRepository(Layout\Page::class)->findOneBy([
                    'website' => $website,
                    'slug' => $params->reference,
                ]);
                if (!$existingPage) {
                    $layout = $this->addLayoutPage($params);
                    $position = $website->getId() > 0 ? count($this->coreLocator->em()->getRepository(Layout\Page::class)->findByWebsiteNotArchived($website)) + 1 : $key + 1;
                    $this->generatePage($layout, $params, $position, $mainPages);
                    $this->coreLocator->em()->persist($layout);
                }
            }
        }

        return $this->pages;
    }

    /**
     * Generate Page.
     */
    private function generatePage(Layout\Layout $layout, mixed $params, int $position, array $mainPages = []): void
    {
        $page = new Layout\Page();
        $page->setAdminName($params->name);
        $page->setWebsite($this->website);
        $page->setAsIndex($params->asIndex);
        $page->setTemplate($params->template.'.html.twig');
        $page->setPosition($position);
        $page->setDeletable($params->deletable);
        $page->setSlug($params->reference);
        $page->setLayout($layout);
        $page->setCreatedBy($this->user);

        if (!$params->deletable) {
            $page->setInfill(true);
        }

        if (property_exists($params, 'secure')) {
            $page->setSecure($params->secure);
        }

        $this->coreLocator->em()->persist($page);
        $this->pages[$params->reference] = $page;

        $this->generateUrl($page, $params->urlAsIndex);

        if (in_array($params->reference, $mainPages)) {
            $configuration = $this->website->getConfiguration();
            $configuration->addPage($page);
            $this->coreLocator->em()->persist($configuration);
        }
    }

    /**
     * Generate Url.
     */
    private function generateUrl(Layout\Page $page, bool $asIndex): void
    {
        $url = new Url();
        $url->setCode(Urlizer::urlize($page->getAdminName()));
        $url->setLocale($this->locale);
        $url->setWebsite($this->website);
        $url->setAsIndex($asIndex);
        $url->setHideInSitemap(!$asIndex);
        $url->setOnline(true);

        if (!empty($this->user)) {
            $url->setCreatedBy($this->user);
        }

        $page->addUrl($url);

        $this->coreLocator->em()->persist($page);
        if ($this->flush) {
            $this->coreLocator->em()->flush();
        }
    }

    /**
     * Generate Layout Page.
     *
     * @throws Exception
     */
    private function addLayoutPage(mixed $params): Layout\Layout
    {
        $layout = $this->addLayout($params->name);
        $this->coreLocator->em()->persist($layout);
        if ($this->flush) {
            $this->coreLocator->em()->flush();
        }
        $this->layoutManager->setGridZone($layout);

        return $layout;
    }

    /**
     * Generate Layout.
     */
    public function addLayout(string $adminName, bool $dbPosition = false): Layout\Layout
    {
        $position = $dbPosition ? count($this->coreLocator->em()->getRepository(Layout\Layout::class)->findBy(['website' => $this->website])) + 1 : $this->layoutPosition;

        $layout = new Layout\Layout();
        $layout->setWebsite($this->website);
        $layout->setAdminName($adminName);
        $layout->setPosition($position);

        if (!empty($this->user)) {
            $layout->setCreatedBy($this->user);
        }

        ++$this->layoutPosition;

        return $layout;
    }

    /**
     * To set Website.
     */
    public function setWebsite(Website $website): void
    {
        $this->website = $website;
    }

    /**
     * To set locale.
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }
}
