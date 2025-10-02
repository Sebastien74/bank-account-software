<?php

declare(strict_types=1);

namespace App\Service;

/**
 * CspNonceInterface.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface CspNonceInterface
{
    public function getNonce(): string;
}
