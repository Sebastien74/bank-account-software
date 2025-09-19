<?php

declare(strict_types=1);

namespace App\Controller\Back;

use App\Controller\BaseController;
use App\Entity\Wallet\Category;
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
    protected mixed $entityClassname = SubCategory::class;

    #[Route('/index/{category}', name: 'admin_subcategory_index', methods: 'GET|POST')]
    public function index(Category $category): Response
    {
        $paginator = $this->getPagination();
        if ($paginator instanceof RedirectResponse) {
            return $paginator;
        }

        $formManager = $this->globalFormManager;
        $formManager->setForm(SubCategoryType::class, new SubCategory());
        $form = $formManager->getForm();
        if ($formManager->getRedirection()) {
            return $this->redirect($formManager->getRedirection());
        }

        $categorytype = $this->coreLocator->em()->getRepository(Category::class)->find($category);
        if (!$categorytype) {
            throw $this->createNotFoundException($this->coreLocator->translator()->trans("Cette page n'existe pas !", [], 'admin'));
        }

        return $this->render('back/index.html.twig', array_merge($this->defaultArguments(), [
            'form' => $form->createView(),
            'formErrors' => $form->isSubmitted() && !$form->isValid(),
            'pagination' => $paginator,
        ]));
    }

    #[Route('/edit/{subcategory}', name: 'admin_subcategory_edit', methods: 'GET|POST')]
    public function edit(SubCategory $subcategory): Response
    {
        $formManager = $this->globalFormManager;
        $formManager->setForm(SubCategoryType::class, $subcategory);
        $form = $formManager->getForm();
        if ($formManager->getRedirection()) {
            return $this->redirect($formManager->getRedirection());
        }

        return $this->render('back/edit.html.twig', array_merge($this->defaultArguments(), [
            'form' => $form->createView(),
            'entity' => $subcategory,
        ]));
    }

    #[Route('/delete/{subcategory}', name: 'admin_subcategory_delete', methods: 'GET')]
    public function delete(SubCategory $subcategory): RedirectResponse
    {
        return $this->redirect($this->globalFormManager->delete($subcategory));
    }
}
