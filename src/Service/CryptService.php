<?php

declare(strict_types=1);

namespace App\Service;

/**
 * CryptService.
 *
 * Manage string encryption.
 *
 * @author Sébastien FOURNIER <fournier.sebastien@outlook.com>
 */
class CryptService
{
    private const string SECRET_KEY = '4d5d63a83bb68c298be7a212b2d939ab2b28fe39';
    private const string SECRET_IV = '06efe6843c7322ef3c39aead28be85f4581bc567';

    /**
     * Encrypt or decrypt a string.
     *
     * @param string $action : e -> Encrypt, d -> decrypt
     */
    public function execute(string $string, string $action = 'e'): bool|string|null
    {
        $output = false;
        $encryptMethod = 'AES-256-CBC';
        $key = hash('sha256', self::SECRET_KEY);
        $iv = substr(hash('sha256', self::SECRET_IV), 0, 16);

        if ('e' == $action) {
            $output = base64_encode(openssl_encrypt($string, $encryptMethod, $key, 0, $iv));
        } elseif ('d' == $action) {
            $output = openssl_decrypt(base64_decode($string), $encryptMethod, $key, 0, $iv);
        }

        return $output;
    }
}
