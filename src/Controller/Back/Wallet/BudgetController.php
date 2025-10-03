<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * BudgetController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/budgets/', schemes: '%protocol%')]
class BudgetController extends BaseController
{
    /**
     * Budget index.
     */
    #[Route('index', name: 'admin_budget_index', methods: 'GET|POST')]
    public function index(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des budgets', [], 'back');

        return parent::index();
    }

    /**
     * Budget edit.
     */
    #[Route('edit/{budget}', name: 'admin_budget_edit', methods: 'GET|POST')]
    public function edit(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des budgets', [], 'back');

        return parent::edit();
    }
}
