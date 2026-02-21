<?php

declare(strict_types=1);

namespace App\Form\Manager\Security;

use App\Entity\Security\User;
use App\Form\Model\Security\Admin\PasswordResetModel;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * ConfirmPasswordManager.
 *
 * Manage User security password.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
readonly class ConfirmPasswordManager
{
    /**
     * ConfirmPasswordManager constructor.
     */
    public function __construct(
        private UserPasswordHasherInterface $passwordEncoder,
        private EntityManagerInterface      $entityManager,
    ) {
    }

    /**
     * Set user password.
     *
     * @throws Exception
     */
    public function confirm(PasswordResetModel $passwordResetModel, User $user): void
    {
        $user->setPassword(
            $this->passwordEncoder->hashPassword($user, $passwordResetModel->getPlainPassword())
        );

        $slugsAlert = ['password-info', 'password-alert'];
        $alerts = $user->getAlerts();
        foreach ($slugsAlert as $key => $slug) {
            if (in_array($slug, $alerts)) {
                unset($alerts[$key]);
            }
        }

        $user->setResetPasswordDate(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
        $user->setTokenRequest(null);
        $user->setTokenRequestDate(null);
        $user->setAlerts($alerts);
        $user->setResetPassword(false);

        $this->entityManager->persist($user);
        $this->entityManager->flush();
    }
}
