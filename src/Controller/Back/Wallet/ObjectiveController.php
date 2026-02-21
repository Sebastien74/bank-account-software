<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\Back\BaseController;
use App\Entity\Wallet\Objective;
use App\Form\Type\Wallet\ObjectiveType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ObjectiveController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/back-%security_token%/objectives/', schemes: '%protocol%')]
class ObjectiveController extends BaseController
{
    protected ?string $pageIcon = 'bullseye-arrow';

    protected ?string $classname = Objective::class;
    protected ?string $formType = ObjectiveType::class;

    /**
     * Objective index.
     */
    #[Route('index', name: 'back_objective_index', methods: 'GET|POST')]
    public function index(PaginatorInterface $paginator): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des objectifs', [], 'back');

        return parent::index($paginator);
    }

    /**
     * Objective edit.
     */
    #[Route('edit/{objective}', name: 'back_objective_edit', methods: 'GET|POST')]
    public function edit(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des objectifs', [], 'back');

        return parent::edit();
    }

    /**
     * Objective delete.
     */
    #[Route('/delete/{objective}', name: 'back_objective_delete', methods: 'GET')]
    public function delete(Objective $objective): RedirectResponse
    {
        return $this->redirect($this->globalFormManager->delete($objective));
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(array $items = []): void
    {
        $items[$this->coreLocator->translator()->trans('Objectifs', [], 'breadcrumb')] = 'back_objective_index';
        if ($this->coreLocator->request()->get('objective')) {
            $items[$this->coreLocator->translator()->trans('Édition', [], 'back_breadcrumb')] = 'back_objective_edit';
        }

        parent::breadcrumb($items);
    }
}
