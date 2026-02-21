<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Service\KeyGeneratorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * UtilityController.
 *
 * Security utilities management
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class UtilityController extends BaseController
{
    /**
     * Password generator.
     */
    #[Route([
        'fr' => '/confirm/{token}',
        'en' => '/confirm/{token}',
    ], name: 'security_password_generator', options: ['expose' => true], methods: 'GET', schemes: '%protocol%')]
    public function passwordGenerator(KeyGeneratorInterface $keyGenerator): JsonResponse
    {
        return new JsonResponse(['password' => $keyGenerator->generate(4, 4, 4, 2)]);
    }
}
