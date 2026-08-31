<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\Back\BaseController;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * StatisticController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/back-%security_token%/statistics/', schemes: '%protocol%')]
class StatisticController extends BaseController
{
    protected ?string $pageIcon = 'chart-bar';

    /**
     * Statistics view.
     *
     * @throws Exception
     */
    #[Route('index', name: 'back_statistics', defaults: ['website' => null], methods: 'GET')]
    public function statistics(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Statistiques', [], 'back');

        return $this->render('back/pages/statistics.html.twig', $this->adminArguments('index'));
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(array $items = []): void
    {
        $items[$this->coreLocator->translator()->trans('Statistiques', [], 'breadcrumb')] = 'back_statistics';

        parent::breadcrumb($items);
    }
}
