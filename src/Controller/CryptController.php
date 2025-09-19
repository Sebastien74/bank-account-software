<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\CryptService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CryptController.
 *
 * Manage string encryption
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
#[Route('/front/crypt', schemes: '%protocol%')]
class CryptController extends AbstractController
{
    /**
     * Encrypt.
     */
    #[Route('/encrypt/{string}',
        name: 'front_encrypt',
        options: ['isMainRequest' => false],
        defaults: ['string' => null],
        methods: 'GET'
    )]
    public function encrypt(CryptService $cryptService, ?string $string = null): JsonResponse
    {
        $response = new JsonResponse(['result' => $cryptService->execute($string, 'e')]);
        header('Cache-Control: max-age=31536000');

        return $response;
    }

    /**
     * Decrypt.
     */
    #[Route('/decrypt/{string}',
        name: 'front_decrypt',
        options: ['isMainRequest' => false],
        defaults: ['string' => null],
        methods: 'GET'
    )]
    public function decrypt(CryptService $codeService, ?string $string = null): JsonResponse
    {
        $response = new JsonResponse(['result' => $codeService->execute($string, 'd')]);
        header('Cache-Control: max-age=31536000');

        return $response;
    }
}
