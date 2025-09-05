<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * IndexController.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class IndexController extends AbstractController
{
    /**
     * Index.
     */
    #[Route([
        'fr' => '/',
        'en' => '/',
    ], name: 'front_index', methods: 'GET|POST', priority: 300)]
    public function index(): RedirectResponse
    {
        return $this->redirectToRoute('security_login');
    }
}
