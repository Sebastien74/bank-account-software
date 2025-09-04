<?php

declare(strict_types=1);

namespace App\Controller\Front\Action;

use App\Controller\Front\FrontController;
use App\Entity\Layout\Block;
use App\Entity\Layout\Page;
use App\Entity\Seo\Url;
use App\Model\ViewModel;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * PagesNavigationController.
 *
 * Front sub-navigation renders
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PagesNavigationController extends FrontController
{
    /**
     * View.
     *
     * @throws NonUniqueResultException|\Exception
     */
    public function view(
        Request $request,
        Url $url,
        ?Block $block = null,
        mixed $filter = null,
    ): Response {

        $arguments = [];
        $website = $this->getWebsite();
        $configuration = $website->configuration;
        $arguments['websiteTemplate'] = $configuration->template;
        $repository = $this->coreLocator->em()->getRepository(Page::class);
        $currentFilter = $filter ? $repository->findOneBy(['id' => $filter]) : ($url->getCode() ? $repository->findByUrlCodeAndLocale($website, $url->getCode(), $request->getLocale(), false) : null);
        $parentPage = $currentFilter instanceof Page && $currentFilter->getParent() ? $currentFilter->getParent() : $currentFilter;
        $pages = $parentPage instanceof Page ? $repository->findOnlineAndLocaleByParent($parentPage, $this->coreLocator->locale()) : [];

        foreach ($pages as $key => $page) {
            $pages[$key] = $pageModel = ViewModel::fromEntity($page, $this->coreLocator, ['disabledLayout' => true]);
            if ($url->getCode() === $pageModel->urlCode) {
                unset($pages[$key]);
            }
        }
        $arguments['subNavigation'] = $pages;

        return $this->cache($request, 'front/'.$arguments['websiteTemplate'].'/actions/pages-navigation/view.html.twig', $parentPage, $arguments);
    }
}
