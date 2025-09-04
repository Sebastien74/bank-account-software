<?php

declare(strict_types=1);

namespace App\Controller\Admin\Core;

use App\Controller\Admin\AdminController;
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
        return $this->adminRender('admin/page/core/dashboard.html.twig', [

        ]);
    }
}
