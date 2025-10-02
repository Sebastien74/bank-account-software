<?php

declare(strict_types=1);

namespace App\Controller\Back;

use App\Controller\BaseController;
use App\Entity\Wallet\Category;
use App\Form\Type\Wallet\CategoryType;
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
#[Route('/admin-%security_token%/categories', schemes: '%protocol%')]
class CategoryController extends BaseController
{
    protected ?string $classname = Category::class;
    protected ?string $formType = CategoryType::class;

    /**
     * Category index.
     */
    #[Route('/index/{categorytype}', name: 'admin_category_index', methods: 'GET|POST')]
    public function index(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des catégories', [], 'back');

        return parent::index();
    }

    /**
     * Category edit.
     */
    #[Route('/edit/{category}', name: 'admin_category_edit', methods: 'GET|POST')]
    public function edit(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des catégories', [], 'back');

        return parent::edit();
    }

    /**
     * Category delete.
     */
    #[Route('/delete/{category}', name: 'admin_category_delete', methods: 'GET')]
    public function delete(Category $category): RedirectResponse
    {
        return $this->redirect($this->globalFormManager->delete($category));
    }
}
