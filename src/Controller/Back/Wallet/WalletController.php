<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\BaseController;
use App\Entity\Wallet\Wallet;
use App\Form\Type\Wallet\WalletType;
use Knp\Component\Pager\PaginatorInterface;
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
#[Route('/back-%security_token%/wallets', schemes: '%protocol%')]
class WalletController extends BaseController
{
    protected ?string $pageIcon = 'wallet';

    protected ?string $classname = Wallet::class;
    protected ?string $formType = WalletType::class;

    /**
     * Wallet index.
     */
    #[Route('/index', name: 'back_wallet_index', methods: 'GET|POST')]
    public function index(PaginatorInterface $paginator): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des comptes', [], 'back');
        $this->addBtnLabel = $this->coreLocator->translator()->trans('Ajouter un compte', [], 'back');
        $this->template = 'back/wallet/wallets.html.twig';

        return parent::index($paginator);
    }

    /**
     * Wallet edit.
     */
    #[Route('/edit/{wallet}', name: 'back_wallet_edit', methods: 'GET|POST')]
    public function edit(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des comptes', [], 'back');

        return parent::edit();
    }

    /**
     * Wallet delete.
     */
    #[Route('/delete/{wallet}', name: 'back_wallet_delete', methods: 'GET')]
    public function delete(Wallet $wallet): RedirectResponse
    {
        return $this->redirect($this->globalFormManager->delete($wallet));
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(array $items = []): void
    {
        $items[$this->coreLocator->translator()->trans('Mes comptes', [], 'breadcrumb')] = 'back_wallet_index';
        if ($this->coreLocator->request()->get('wallet')) {
            $items[$this->coreLocator->translator()->trans('Édition', [], 'back_breadcrumb')] = 'back_wallet_edit';
        }

        parent::breadcrumb($items);
    }
}
