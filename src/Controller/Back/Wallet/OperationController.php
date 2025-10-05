<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\BaseController;
use App\Entity\Wallet\Operation;
use App\Form\Manager\GlobalManagerInterface;
use App\Form\Manager\Wallet\OperationInterface;
use App\Form\Type\Wallet\OperationType;
use App\Service\CoreLocatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * WalletController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/back-%security_token%/wallets/operations', schemes: '%protocol%')]
class OperationController extends BaseController
{
    protected int $paginationLimit = 50;
    protected bool $forceEntities = true;

    protected ?string $pageIcon = 'wallet';

    protected ?string $classname = Operation::class;
    protected ?string $formType = OperationType::class;

    /**
     * OperationController constructor.
     */
    public function __construct(
        protected CoreLocatorInterface $coreLocator,
        protected GlobalManagerInterface $globalFormManager,
        protected OperationInterface $operation,
    ) {
        parent::__construct($coreLocator, $globalFormManager, $operation);
    }

    /**
     * Operation index.
     */
    #[Route('/index/{wallet}', name: 'back_operation_index', methods: 'GET|POST')]
    public function index(PaginatorInterface $paginator): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Mes opérations', [], 'back');
        $this->template = 'back/wallet/operations.html.twig';

        return parent::index($paginator);
    }

    /**
     * Operation edit.
     */
    #[Route('/edit/{operation}', name: 'back_operation_edit', methods: 'GET|POST')]
    public function edit(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Mes opérations', [], 'back');

        return parent::edit();
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(array $items = []): void
    {
        $items[$this->coreLocator->translator()->trans('Mes comptes', [], 'breadcrumb')] = 'back_wallet_index';
        $items[$this->coreLocator->translator()->trans('Opérations', [], 'breadcrumb')] = 'back_operation_index';
        if ($this->coreLocator->request()->get('objective')) {
            $items[$this->coreLocator->translator()->trans('Édition', [], 'back_breadcrumb')] = 'back_operation_edit';
        }

        parent::breadcrumb($items);
    }
}
