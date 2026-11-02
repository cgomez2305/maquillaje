<?php
declare(strict_types=1);

namespace App\Support;

/** Cifrado simétrico con APP_KEY (sodium_crypto_secretbox), p. ej. para el secreto TOTP. */
final class Crypto
{
    public static function encrypt(string $plain): string
    {
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return $nonce . sodium_crypto_secretbox($plain, $nonce, app_key());
    }

    public static function decrypt(string $cipher): ?string
    {
        if (strlen($cipher) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) {
            return null;
        }
        $nonce = substr($cipher, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open(substr($cipher, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, app_key());
        return $plain === false ? null : $plain;
    }
}
