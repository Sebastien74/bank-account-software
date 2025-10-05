<?php

declare(strict_types=1);

namespace App\Controller\Back\Wallet;

use App\Controller\BaseController;
use App\Entity\Wallet\Category;
use App\Entity\Wallet\SubCategory;
use App\Form\Type\Wallet\SubCategoryType;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * SubCategoryController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/back-%security_token%/sub-categories', schemes: '%protocol%')]
class SubCategoryController extends BaseController
{
    protected ?string $pageIcon = 'list-alt';

    protected ?string $classname = SubCategory::class;
    protected ?string $formType = SubCategoryType::class;

    /**
     * SubCategory index.
     */
    #[Route('/index/{category}', name: 'back_subcategory_index', methods: 'GET|POST')]
    public function index(PaginatorInterface $paginator): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des catégories', [], 'back');

        return parent::index($paginator);
    }

    /**
     * SubCategory edit.
     */
    #[Route('/edit/{subcategory}', name: 'back_subcategory_edit', methods: 'GET|POST')]
    public function edit(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des catégories', [], 'back');

        return parent::edit();
    }

    /**
     * SubCategory delete.
     */
    #[Route('/delete/{subcategory}', name: 'back_subcategory_delete', methods: 'GET')]
    public function delete(SubCategory $subcategory): RedirectResponse
    {
        return $this->redirect($this->globalFormManager->delete($subcategory));
    }

    /**
     * To set breadcrumb.
     */
    protected function breadcrumb(array $items = []): void
    {
        $categoryRequest = $this->coreLocator->request()->get('category');
        $category = $categoryRequest ? $this->coreLocator->em()->getRepository(Category::class)->find($categoryRequest) : false;
        $categoryTypeId = $category ? $category->getOperationtype()->getId() : ($this->entity ? $this->entity->getCategory()->getOperationtype()->getId() : false);

        $items[$this->coreLocator->translator()->trans("Types d'opérations", [], 'breadcrumb')] = 'back_operationtype_index';
        $items[$this->coreLocator->translator()->trans('Catégories', [], 'breadcrumb')] = $this->coreLocator->router()->generate('back_category_index', ['operationtype' => $categoryTypeId], UrlGeneratorInterface::ABSOLUTE_URL);
        $items[$this->coreLocator->translator()->trans('Sous-catégories', [], 'breadcrumb')] = 'back_subcategory_index';

        if ($this->coreLocator->request()->get('subcategory')) {
            $items[$this->coreLocator->translator()->trans('Édition', [], 'breadcrumb')] = 'back_subcategory_edit';
        }

        parent::breadcrumb($items);
    }
}
