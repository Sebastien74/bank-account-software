<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\BaseController;
use App\Entity\Security\User;
use App\Form\Type\Security\LoginType;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * SecurityController.
 *
 * Main security controller to manage auth User
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Route('/secure/user/{_locale}/%security_token%', schemes: '%protocol%')]
class SecurityController extends BaseController
{
    /**
     * Login page.
     *
     * @throws Exception
     */
    #[Route([
        'fr' => '/login',
        'en' => '/login',
    ], name: 'security_login', methods: 'GET|POST', priority: 300)]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof User && $this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard');
        }

        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);

        return $this->render('security/login.html.twig', array_merge($this->defaultArguments(), [
            'form' => $form->createView(),
            'login_type' => $_ENV['SECURITY_ADMIN_LOGIN_TYPE'],
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
        ]));
    }
}
