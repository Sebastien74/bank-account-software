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
#[Route('/back-%security_token%', schemes: '%protocol%')]
class DashboardController extends BaseController
{
    protected ?string $pageIcon = 'tachometer-alt';

    /**
     * Dashboard view.
     */
    #[Route('/dashboard', name: 'back_dashboard', methods: 'GET')]
    public function view(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Tableau de bord', [], 'back');

        return $this->render('back/pages/dashboard.html.twig', array_merge($this->defaultArguments(), [

        ]));
    }
}
