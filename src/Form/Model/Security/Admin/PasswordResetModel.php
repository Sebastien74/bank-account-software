<?php

declare(strict_types=1);

namespace App\Form\Model\Security\Admin;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * PasswordResetModel.
 *
 * Set User security asserts form attributes
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class PasswordResetModel
{
    #[Assert\NotBlank(message: 'Veuillez saisir un mot de passe.')]
    #[Assert\Regex(
        pattern: '/(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9]).{8,}/',
        message: 'Le mot de passe doit comporter au moins 8 caractères, contenir au moins un chiffre, une majuscule et une minuscule.',
    )]
    public ?string $plainPassword = null;

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }
}
