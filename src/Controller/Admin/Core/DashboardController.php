<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
use App\Entity\Core\Domain;
use App\Entity\Core\Website;
use App\Entity\Seo\NotFoundUrl;
use App\Entity\Seo\Url;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * DashboardController.
 *
 * Dashboard management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%', schemes: '%protocol%')]
class DashboardController extends AdminController
{
    /**
     * Dashboard view.
     */
    #[Route('/dashboard/{website}', name: 'admin_dashboard', defaults: ['website' => null], methods: 'GET')]
    public function view(Request $request, PaginatorInterface $paginator): Response
    {
        $website = $this->getWebsite();
        $notFoundsLimit = 50;
        $noSeoCounts = $this->coreLocator->em()->getRepository(Url::class)->countEmptyLocalesSEO($website->entity);

        return $this->adminRender('admin/page/core/dashboard.html.twig', [
            'notFoundUrls' => $this->getNotFoundUrls($website->entity, $notFoundsLimit),
            'notFoundsLimit' => $notFoundsLimit,
            'noSeoCounts' => $noSeoCounts,
        ]);
    }

    /**
     * Get NotFoundUrl[].
     */
    private function getNotFoundUrls(Website $website, int $limit): array
    {
        $domains = $this->coreLocator->em()->getRepository(Domain::class)->findByConfiguration($website->getConfiguration());

        return $this->coreLocator->em()->getRepository(NotFoundUrl::class)->findFrontWithoutRedirections($website, $domains, $limit);
    }
}
