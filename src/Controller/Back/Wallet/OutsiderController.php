<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\Back\BaseController;
use App\Entity\Wallet\Outsider;
use App\Form\Type\Wallet\OutsiderType;
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
#[Route('/back-%security_token%/wallets/outsiders', schemes: '%protocol%')]
class OutsiderController extends BaseController
{
    protected ?string $pageIcon = 'list-alt';

    protected ?string $classname = Outsider::class;
    protected ?string $formType = OutsiderType::class;

    /**
     * Outsider index.
     */
    #[Route('/index', name: 'back_outsider_index', methods: 'GET|POST')]
    public function index(PaginatorInterface $paginator): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des bénéficiaires', [], 'back');

        return parent::index($paginator);
    }

    /**
     * Outsider edit.
     */
    #[Route('/edit/{outsider}', name: 'back_outsider_edit', methods: 'GET|POST')]
    public function edit(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des bénéficiaires', [], 'back');

        return parent::edit();
    }

    /**
     * Outsider delete.
     */
    #[Route('/delete/{outsider}', name: 'back_outsider_delete', methods: 'GET')]
    public function delete(Outsider $outsider): RedirectResponse
    {
        return $this->redirect($this->globalFormManager->delete($outsider));
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(array $items = []): void
    {
        $items[$this->coreLocator->translator()->trans("Bénéficiaires", [], 'breadcrumb')] = 'back_outsider_index';
        if ($this->coreLocator->request()->attributes->get('outsider')) {
            $items[$this->coreLocator->translator()->trans('Édition', [], 'back_breadcrumb')] = 'back_outsider_edit';
        }

        parent::breadcrumb($items);
    }
}
