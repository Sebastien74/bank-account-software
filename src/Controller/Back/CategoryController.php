<?php

declare(strict_types=1);

namespace App\Controller\Back;

use App\Controller\BaseController;
use App\Entity\Wallet\Category;
use App\Form\Type\Wallet\CategoryType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
    protected mixed $entityClassname = Category::class;

    #[Route('/index/{categorytype}', name: 'admin_category_index', methods: 'GET|POST')]
    public function index($categorytype): Response
    {
        $paginator = $this->getPagination();
        if ($paginator instanceof RedirectResponse) {
            return $paginator;
        }

        $formManager = $this->globalFormManager;
        $formManager->setForm(CategoryType::class, new Category());
        $form = $formManager->getForm();
        if ($formManager->getRedirection()) {
            return $this->redirect($formManager->getRedirection());
        }

        $categorytype = $this->coreLocator->em()->getRepository(\App\Entity\Wallet\CategoryType::class)->find($categorytype);
        if (!$categorytype) {
            throw $this->createNotFoundException($this->coreLocator->translator()->trans("Cette page n'existe pas !", [], 'admin'));
        }

        return $this->render('back/index.html.twig', array_merge($this->defaultArguments(), [
            'form' => $form->createView(),
            'formErrors' => $form->isSubmitted() && !$form->isValid(),
            'pagination' => $paginator,
        ]));
    }

    #[Route('/edit/{category}', name: 'admin_category_edit', methods: 'GET|POST')]
    public function edit(Request $request, Category $category): Response
    {
        $formManager = $this->globalFormManager;
        $formManager->setForm(CategoryType::class, $category);
        $form = $formManager->getForm();
        if ($formManager->getRedirection()) {
            return $this->redirect($formManager->getRedirection());
        }

        return $this->render('back/edit.html.twig', array_merge($this->defaultArguments(), [
            'form' => $form->createView(),
            'entity' => $category,
        ]));
    }

    #[Route('/delete/{category}', name: 'admin_category_delete', methods: 'GET')]
    public function delete(): RedirectResponse
    {
        return $this->redirect($this->globalFormManager->delete($this->entityClassname));
    }
}
