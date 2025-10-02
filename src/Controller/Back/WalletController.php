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
    protected ?string $classname = Wallet::class;
    protected ?string $formType = WalletType::class;

    /**
     * Wallet index.
     */
    #[Route('/index', name: 'admin_wallet_index', methods: 'GET|POST')]
    public function index(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des comptes', [], 'back');
        $this->template = 'back/wallet/wallets.html.twig';

        return parent::index();
    }

    /**
     * Wallet edit.
     */
    #[Route('/edit/{wallet}', name: 'admin_wallet_edit', methods: 'GET|POST')]
    public function edit(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des comptes', [], 'back');

        return parent::edit();
    }

    /**
     * Wallet delete.
     */
    #[Route('/delete/{wallet}', name: 'admin_wallet_delete', methods: 'GET')]
    public function delete(Wallet $wallet): RedirectResponse
    {
        return $this->redirect($this->globalFormManager->delete($wallet));
    }
}
