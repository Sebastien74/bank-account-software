<?php

declare(strict_types=1);

namespace App\Controller\Back;

use App\Controller\BaseController;
use App\Entity\Wallet\Wallet;
use App\Form\Type\Wallet\WalletType;
use Symfony\Component\HttpFoundation\Request;
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
    #[Route('/index', name: 'admin_wallet_index', methods: 'GET|POST')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(WalletType::class, new Wallet());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $wallet = $form->getData();
            $this->coreLocator->em()->persist($wallet);
            $this->coreLocator->em()->flush();
            return $this->redirectToRoute('admin_wallet_index');
        }

        return $this->render('back/pages/wallets.html.twig', array_merge($this->defaultArguments(), [
            'form' => $form->createView(),
            'formErrors' => $form->isSubmitted() && !$form->isValid(),
            'wallets' => $this->coreLocator->em()->getRepository(Wallet::class)->findAll(),
        ]));
    }

    #[Route('/edit/{wallet}', name: 'admin_wallet_edit', methods: 'GET|POST')]
    public function edit(Request $request, Wallet $wallet): Response
    {
        $form = $this->createForm(WalletType::class, $wallet);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $wallet = $form->getData();
            $this->coreLocator->em()->persist($wallet);
            $this->coreLocator->em()->flush();
            return $this->redirectToRoute('admin_wallet_edit', ['wallet' => $wallet->getId()]);
        }

        return $this->render('back/pages/wallet-edit.html.twig', array_merge($this->defaultArguments(), [
            'form' => $form->createView(),
            'wallet' => $wallet,
        ]));
    }

    #[Route('/operations/{wallet}', name: 'admin_wallet_operations', methods: 'GET|POST')]
    public function operations(Request $request, Wallet $wallet): Response
    {
        $form = $this->createForm(WalletType::class, $wallet);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $wallet = $form->getData();
            $this->coreLocator->em()->persist($wallet);
            $this->coreLocator->em()->flush();
            return $this->redirectToRoute('admin_wallet_edit', ['wallet' => $wallet->getId()]);
        }

        return $this->render('back/pages/wallet-operations.html.twig', array_merge($this->defaultArguments(), [
            'form' => $form->createView(),
            'wallet' => $wallet,
        ]));
    }
}
