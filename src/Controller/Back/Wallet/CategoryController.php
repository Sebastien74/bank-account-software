<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\Back\BaseController;
use App\Entity\Wallet\Category;
use App\Form\Type\Wallet\CategoryType;
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
#[Route('/back-%security_token%/categories', schemes: '%protocol%')]
class CategoryController extends BaseController
{
    protected ?string $pageIcon = 'list-alt';

    protected ?string $classname = Category::class;
    protected ?string $formType = CategoryType::class;

    /**
     * Category index.
     */
    #[Route('/index/{operationtype}', name: 'back_category_index', methods: 'GET|POST')]
    public function index(PaginatorInterface $paginator): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des catégories', [], 'back');

        return parent::index($paginator);
    }

    /**
     * Category edit.
     */
    #[Route('/edit/{category}', name: 'back_category_edit', methods: 'GET|POST')]
    public function edit(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des catégories', [], 'back');

        return parent::edit();
    }

    /**
     * Category delete.
     */
    #[Route('/delete/{category}', name: 'back_category_delete', methods: 'GET')]
    public function delete(Category $category): RedirectResponse
    {
        return $this->redirect($this->globalFormManager->delete($category));
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(array $items = []): void
    {
        $items[$this->coreLocator->translator()->trans("Types d'opérations", [], 'breadcrumb')] = 'back_operationtype_index';
        $items[$this->coreLocator->translator()->trans('Catégories', [], 'breadcrumb')] = 'back_category_index';
        if ($this->coreLocator->request()->get('category')) {
            $items[$this->coreLocator->translator()->trans('Édition', [], 'breadcrumb')] = 'back_category_edit';
        }

        parent::breadcrumb($items);
    }
}
