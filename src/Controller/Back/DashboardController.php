<?php

declare(strict_types=1);

namespace App\Controller\Back;

use App\Controller\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * DashboardController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%', schemes: '%protocol%')]
class DashboardController extends BaseController
{
    /**
     * Dashboard view.
     */
    #[Route('/dashboard/{website}', name: 'admin_dashboard', defaults: ['website' => null], methods: 'GET')]
    public function view(): Response
    {
        return $this->render('back/dashboard/dashboard.html.twig', array_merge($this->defaultArguments(), [

        ]));
    }
}
