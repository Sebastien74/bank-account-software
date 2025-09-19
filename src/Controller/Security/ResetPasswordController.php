<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\BaseController;
use App\Form\Manager\Security\ConfirmPasswordManager;
use App\Form\Manager\Security\ResetPasswordManager;
use App\Form\Type\Security\PasswordRequestType;
use App\Form\Type\Security\PasswordResetType;
use App\Repository\Security\UserRepository;
use App\Security\BaseAuthenticator;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * ResetPasswordController.
 *
 * Security reset password management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Route('/secure/user/reset-password/{_locale}', schemes: '%protocol%')]
class ResetPasswordController extends BaseController
{
    /**
     * Request password.
     *
     * @throws Exception
     */
    #[Route('/request', name: 'security_password_request', methods: 'GET|POST')]
    public function request(
        Request $request,
        BaseAuthenticator $baseAuthenticator,
        ResetPasswordManager $manager)
    : \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response {

        $form = $this->createForm(PasswordRequestType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $baseAuthenticator->checkRecaptcha($request, true)) {
            $manager->send($form->getData());
            return $this->redirectToRoute('security_password_request');
        }

        if ($request->get('expire')) {
            $session = new Session();
            $session->getFlashBag()->add('warning', $this->coreLocator->translator()->trans('Votre mot de passe a expiré, vous devez le réinitialiser.', [], 'security_cms'));
        }

        return $this->render('security/password-request.html.twig', array_merge($this->defaultArguments(), [
            'form' => $form->createView(),
        ]));
    }

    /**
     * Reset password.
     *
     * @throws Exception
     */
    #[Route('/confirm/{token}', name: 'security_password_confirm', methods: 'GET|POST')]
    public function confirm(
        Request $request,
        string $token,
        UserRepository $repository,
        BaseAuthenticator $baseAuthenticator,
        ConfirmPasswordManager $manager,
        AuthorizationCheckerInterface $authorizationChecker,
    ): \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response {

        $user = $repository->findOneBy(['tokenRequest' => urldecode($token)]);

        $form = $this->createForm(PasswordResetType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $baseAuthenticator->checkRecaptcha($request, true)) {
            $manager->confirm($form->getData(), $user);
            $this->addFlash('success', $this->coreLocator->translator()->trans('Votre mot de passe a été modifié avec succès.', [], 'security_cms'));

            return $this->redirectToRoute('security_login');
        }

        if (!$user && $authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_dashboard', ['website' => $this->getWebsite()->id]);
        } elseif (!$user) {
            throw $this->createAccessDeniedException($this->coreLocator->translator()->trans('Accès refusé.', [], 'security_cms'));
        }

        return $this->render('security/password-confirm.html.twig', array_merge($this->defaultArguments(), [
            'form' => $form->createView(),
        ]));
    }
}
