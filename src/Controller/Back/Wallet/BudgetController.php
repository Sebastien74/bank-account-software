<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\BaseController;
use App\Entity\Wallet\Budget;
use App\Form\Type\Wallet\BudgetType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * BudgetController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/back-%security_token%/budgets/', schemes: '%protocol%')]
class BudgetController extends BaseController
{
    protected ?string $pageIcon = 'envelope-open-dollar';

    protected ?string $classname = Budget::class;
    protected ?string $formType = BudgetType::class;

    /**
     * Budget index.
     */
    #[Route('index', name: 'back_budget_index', methods: 'GET|POST')]
    public function index(PaginatorInterface $paginator): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des budgets', [], 'back');

        return parent::index($paginator);
    }

    /**
     * Budget edit.
     */
    #[Route('edit/{budget}', name: 'back_budget_edit', methods: 'GET|POST')]
    public function edit(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des budgets', [], 'back');

        return parent::edit();
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(array $items = []): void
    {
        $items[$this->coreLocator->translator()->trans('Budgets', [], 'breadcrumb')] = 'back_budget_index';
        if ($this->coreLocator->request()->get('objective')) {
            $items[$this->coreLocator->translator()->trans('Édition', [], 'back_breadcrumb')] = 'back_budget_edit';
        }

        parent::breadcrumb($items);
    }
}
