<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * StatisticController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/statistics/', schemes: '%protocol%')]
class StatisticController extends BaseController
{
    /**
     * Statistics view.
     */
    #[Route('index', name: 'admin_statistics', defaults: ['website' => null], methods: 'GET')]
    public function statistics(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Statistiques', [], 'back');

        return $this->render('back/pages/statistics.html.twig', array_merge($this->defaultArguments(), [

        ]));
    }
}
