<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\BaseController;
use App\Service\KeyGeneratorService;
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
    #[Route('/admin-%security_token%/security/utility/password-generator', name: 'security_password_generator', options: ['expose' => true], methods: 'GET', schemes: '%protocol%')]
    public function passwordGenerator(KeyGeneratorService $keyGeneratorService): JsonResponse
    {
        return new JsonResponse(['password' => $keyGeneratorService->generate(4, 4, 4, 2)]);
    }
}
