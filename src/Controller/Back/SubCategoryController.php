<?php

declare(strict_types=1);

namespace App\Controller\Back;

use App\Controller\BaseController;
use App\Entity\Wallet\SubCategory;
use App\Form\Type\Wallet\SubCategoryType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * SubCategoryController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/sub-categories', schemes: '%protocol%')]
class SubCategoryController extends BaseController
{
    protected ?string $classname = SubCategory::class;
    protected ?string $formType = SubCategoryType::class;

    /**
     * SubCategory index.
     */
    #[Route('/index/{category}', name: 'admin_subcategory_index', methods: 'GET|POST')]
    public function index(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des catégories', [], 'back');

        return parent::index();
    }

    /**
     * SubCategory edit.
     */
    #[Route('/edit/{subcategory}', name: 'admin_subcategory_edit', methods: 'GET|POST')]
    public function edit(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des catégories', [], 'back');

        return parent::edit();
    }

    /**
     * SubCategory delete.
     */
    #[Route('/delete/{subcategory}', name: 'admin_subcategory_delete', methods: 'GET')]
    public function delete(SubCategory $subcategory): RedirectResponse
    {
        return $this->redirect($this->globalFormManager->delete($subcategory));
    }
}
