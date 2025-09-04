<?php

declare(strict_types=1);

namespace App\Service\Core;

use Random\RandomException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * CspNonceGenerator.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CspNonceGenerator
{
    private const string ATTRIBUTE_KEY = '_csp_nonce';

    /**
     * CspNonceGenerator constructor.
     */
    public function __construct(private readonly RequestStack $requestStack) {}

    /**
     * @throws RandomException
     */
    public function getNonce(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return '';
        }

        if (!$request->attributes->has(self::ATTRIBUTE_KEY)) {
            $request->attributes->set(self::ATTRIBUTE_KEY, base64_encode(random_bytes(16)));
        }

        return $request->attributes->get(self::ATTRIBUTE_KEY);
    }
}
