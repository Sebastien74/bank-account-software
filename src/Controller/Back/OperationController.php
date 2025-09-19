<?php

declare(strict_types=1);

namespace App\Controller\Back;

use App\Controller\BaseController;
use App\Entity\Wallet\Operation;
use App\Entity\Wallet\Wallet;
use App\Form\Type\Wallet\OperationType;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
    protected mixed $entityClassname = Operation::class;

    #[Route('/index/{wallet}', name: 'admin_wallet_operations', methods: 'GET|POST')]
    public function operations(Wallet $wallet): Response
    {
        $paginator = $this->getPagination();
        if ($paginator instanceof RedirectResponse) {
            return $paginator;
        }

        $formManager = $this->globalFormManager;
        $formManager->setForm(OperationType::class, new Operation());
        $form = $formManager->getForm();
        if ($formManager->getRedirection()) {
            return $this->redirect($formManager->getRedirection());
        }

        return $this->render('back/wallet/operations.html.twig', array_merge($this->defaultArguments(), [
            'form' => $form->createView(),
            'formErrors' => $form->isSubmitted() && !$form->isValid(),
            'pagination' => $paginator,
            'wallet' => $wallet,
        ]));
    }
}
