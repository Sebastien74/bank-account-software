<?php

declare(strict_types=1);

namespace App\Controller\Back;

use App\Controller\BaseController;
use App\Entity\Wallet\CategoryType;
use App\Form\Type\Wallet\CategoryTypeType;
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
#[Route('/admin-%security_token%/categories-types', schemes: '%protocol%')]
class CategoryTypeController extends BaseController
{
    protected mixed $entityClassname = CategoryType::class;

    #[Route('/index', name: 'admin_categorytype_index', methods: 'GET|POST')]
    public function index(): Response
    {
        $paginator = $this->getPagination();
        if ($paginator instanceof RedirectResponse) {
            return $paginator;
        }
        $formManager = $this->globalFormManager;
        $formManager->setForm(CategoryTypeType::class, new CategoryType());
        $form = $formManager->getForm();
        if ($formManager->getRedirection()) {
            return $this->redirect($formManager->getRedirection());
        }

        return $this->render('back/index.html.twig', array_merge($this->defaultArguments(), [
            'form' => $form->createView(),
            'formErrors' => $form->isSubmitted() && !$form->isValid(),
            'pagination' => $paginator,
        ]));
    }

    #[Route('/edit/{categorytype}', name: 'admin_categorytype_edit', methods: 'GET|POST')]
    public function edit(Request $request, CategoryType $categorytype): Response
    {
        $formManager = $this->globalFormManager;
        $formManager->setForm(CategoryTypeType::class, $categorytype);
        $form = $formManager->getForm();
        if ($formManager->getRedirection()) {
            return $this->redirect($formManager->getRedirection());
        }

        return $this->render('back/edit.html.twig', array_merge($this->defaultArguments(), [
            'form' => $form->createView(),
            'entity' => $categorytype,
        ]));
    }

    #[Route('/delete/{categorytype}', name: 'admin_categorytype_delete', methods: 'GET')]
    public function delete(): RedirectResponse
    {
        return $this->redirect($this->globalFormManager->delete($this->entityClassname));
    }
}
