<?php

declare(strict_types=1);

namespace App\Controller;

use Exception;
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

    /**
     * To logout user.
     *
     * @throws Exception
     */
    #[Route('/logout', name: 'app_logout', methods: 'GET', schemes: '%protocol%', priority: 1000)]
    public function logout(): void
    {
        /* controller can be blank: it will never be executed! */
        throw new Exception("Don't forget to activate logout in security.yaml");
    }
}
