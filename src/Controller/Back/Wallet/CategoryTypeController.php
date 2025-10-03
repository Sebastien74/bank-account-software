<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\BaseController;
use App\Entity\Wallet\CategoryType;
use App\Form\Type\Wallet\CategoryTypeType;
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
#[Route('/admin-%security_token%/categories-types', schemes: '%protocol%')]
class CategoryTypeController extends BaseController
{
    protected ?string $classname = CategoryType::class;
    protected ?string $formType = CategoryTypeType::class;

    /**
     * CategoryType index.
     */
    #[Route('/index', name: 'admin_categorytype_index', methods: 'GET|POST')]
    public function index(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des catégories', [], 'back');

        return parent::index();
    }

    /**
     * CategoryType edit.
     */
    #[Route('/edit/{categorytype}', name: 'admin_categorytype_edit', methods: 'GET|POST')]
    public function edit(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des catégories', [], 'back');

        return parent::edit();
    }

    /**
     * CategoryType delete.
     */
    #[Route('/delete/{categorytype}', name: 'admin_categorytype_delete', methods: 'GET')]
    public function delete(CategoryType $categorytype): RedirectResponse
    {
        return $this->redirect($this->globalFormManager->delete($categorytype));
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(array $items = []): void
    {
        $items[$this->coreLocator->translator()->trans('Types', [], 'breadcrumb')] = 'admin_categorytype_index';
        if ($this->coreLocator->request()->get('categorytype')) {
            $items[$this->coreLocator->translator()->trans('Édition', [], 'admin_breadcrumb')] = 'admin_categorytype_edit';
        }

        parent::breadcrumb($items);
    }
}
