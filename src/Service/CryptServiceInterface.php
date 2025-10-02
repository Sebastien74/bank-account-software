<?php

declare(strict_types=1);

namespace App\Service;

/**
 * CryptServiceInterface.
 *
 * Manage string encryption.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface CryptServiceInterface
{
    public function execute(string $string, string $action = 'e'): bool|string|null;
}