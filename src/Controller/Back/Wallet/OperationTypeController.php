<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\BaseController;
use App\Entity\Wallet\OperationType;
use App\Form\Type\Wallet\OperationTypeType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * CategoryController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/back-%security_token%/categories-types', schemes: '%protocol%')]
class OperationTypeController extends BaseController
{
    protected ?string $pageIcon = 'list-alt';

    protected ?string $classname = OperationType::class;
    protected ?string $formType = OperationTypeType::class;

    /**
     * OperationType index.
     */
    #[Route('/index', name: 'back_operationtype_index', methods: 'GET|POST')]
    public function index(PaginatorInterface $paginator): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des catégories', [], 'back');

        return parent::index($paginator);
    }

    /**
     * OperationType edit.
     */
    #[Route('/edit/{operationtype}', name: 'back_operationtype_edit', methods: 'GET|POST')]
    public function edit(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des catégories', [], 'back');

        return parent::edit();
    }

    /**
     * OperationType delete.
     */
    #[Route('/delete/{operationtype}', name: 'back_operationtype_delete', methods: 'GET')]
    public function delete(OperationType $operationtype): RedirectResponse
    {
        return $this->redirect($this->globalFormManager->delete($operationtype));
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(array $items = []): void
    {
        $items[$this->coreLocator->translator()->trans("Types d'opérations", [], 'breadcrumb')] = 'back_operationtype_index';
        if ($this->coreLocator->request()->get('operationtype')) {
            $items[$this->coreLocator->translator()->trans('Édition', [], 'back_breadcrumb')] = 'back_operationtype_edit';
        }

        parent::breadcrumb($items);
    }
}
