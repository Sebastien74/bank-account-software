<?php

declare(strict_types=1);

namespace App\Controller\Back;

use App\Controller\BaseController;
use App\Entity\Wallet\Wallet;
use App\Form\Type\Wallet\WalletType;
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
#[Route('/admin-%security_token%/wallets', schemes: '%protocol%')]
class WalletController extends BaseController
{
    protected mixed $entityClassname = Wallet::class;

    #[Route('/index', name: 'admin_wallet_index', methods: 'GET|POST')]
    public function index(): Response
    {
        $formManager = $this->globalFormManager;
        $formManager->setForm(WalletType::class, new Wallet());
        $form = $formManager->getForm();
        if ($formManager->getRedirection()) {
            return $this->redirect($formManager->getRedirection());
        }

        return $this->render('back/wallet/wallets.html.twig', array_merge($this->defaultArguments(), [
            'form' => $form->createView(),
            'formErrors' => $form->isSubmitted() && !$form->isValid(),
            'wallets' => $this->coreLocator->em()->getRepository(Wallet::class)->findAll(),
        ]));
    }

    #[Route('/edit/{wallet}', name: 'admin_wallet_edit', methods: 'GET|POST')]
    public function edit(Wallet $wallet): Response
    {
        $formManager = $this->globalFormManager;
        $formManager->setForm(WalletType::class, $wallet);
        $form = $formManager->getForm();
        if ($formManager->getRedirection()) {
            return $this->redirect($formManager->getRedirection());
        }

        return $this->render('back/edit.html.twig', array_merge($this->defaultArguments(), [
            'form' => $form->createView(),
            'entity' => $wallet,
        ]));
    }

    #[Route('/delete/{wallet}', name: 'admin_wallet_delete', methods: 'GET')]
    public function delete(): RedirectResponse
    {
        return $this->redirect($this->globalFormManager->delete($this->entityClassname));
    }
}
