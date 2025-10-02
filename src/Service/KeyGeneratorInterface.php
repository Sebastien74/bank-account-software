<?php

declare(strict_types=1);

namespace App\Service;

/**
 * KeyGeneratorInterface.
 *
 * To generate token, password ...
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
interface KeyGeneratorInterface
{
    public function generate(int $uppers = 0, int $lowers = 0, int $specialCharacters = 0, int $numbers = 0): string;
}