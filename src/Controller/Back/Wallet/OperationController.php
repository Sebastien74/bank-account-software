<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\BaseController;
use App\Entity\Wallet\Operation;
use App\Form\Type\Wallet\OperationType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * WalletController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/wallets/operations', schemes: '%protocol%')]
class OperationController extends BaseController
{
    protected ?string $classname = Operation::class;
    protected ?string $formType = OperationType::class;

    /**
     * Operation index.
     */
    #[Route('/index/{wallet}', name: 'admin_operation_index', methods: 'GET|POST')]
    public function index(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Mes opérations', [], 'back');
        $this->template = 'back/wallet/operations.html.twig';

        return parent::index();
    }

    /**
     * Operation edit.
     */
    #[Route('/edit/{operation}', name: 'admin_operation_edit', methods: 'GET|POST')]
    public function edit(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Mes opérations', [], 'back');

        return parent::index();
    }
}
