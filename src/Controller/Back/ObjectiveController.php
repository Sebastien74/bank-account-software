<?php

declare(strict_types=1);

namespace App\Controller\Back;

use App\Controller\BaseController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * ObjectiveController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[IsGranted('ROLE_ADMIN')]
#[Route('/admin-%security_token%/objectives/', schemes: '%protocol%')]
class ObjectiveController extends BaseController
{
    /**
     * Objective index.
     */
    #[Route('index', name: 'admin_objective_index', methods: 'GET|POST')]
    public function index(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des objectifs', [], 'back');

        return parent::index();
    }

    /**
     * Objective edit.
     */
    #[Route('edit/{}', name: 'admin_objective_edit', methods: 'GET|POST')]
    public function edit(): Response
    {
        $this->pageTitle = $this->coreLocator->translator()->trans('Gestion des objectifs', [], 'back');

        return parent::index();
    }
}
