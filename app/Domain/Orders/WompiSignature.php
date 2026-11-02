<?php
declare(strict_types=1);

namespace App\Domain\Orders;

/**
 * Firmas de Wompi (§11).
 * - Integridad del checkout: SHA256(referencia + monto_en_centavos + moneda + secreto_de_integridad).
 * - Eventos: SHA256(valores de signature.properties en orden + timestamp + secreto_de_eventos).
 */
final class WompiSignature
{
    public static function integrity(string $reference, int $amountInCents, string $currency, string $secret): string
    {
        return hash('sha256', $reference . $amountInCents . $currency . $secret);
    }

    /**
     * @param array<string,mixed> $event cuerpo JSON decodificado
     */
    public static function verifyEvent(array $event, string $secret, ?string $headerChecksum = null): bool
    {
        if ($secret === '') {
            return false;
        }
        $props = $event['signature']['properties'] ?? null;
        $checksum = (string) ($event['signature']['checksum'] ?? $headerChecksum ?? '');
        $timestamp = $event['timestamp'] ?? null;
        if (!is_array($props) || $props === [] || $checksum === '' || !is_scalar($timestamp)) {
            return false;
        }
        $concat = '';
        foreach ($props as $path) {
            if (!is_string($path) || !preg_match('/^[a-z_]+(\.[a-z_]+)+$/', $path)) {
                return false;
            }
            $value = $event['data'] ?? [];
            foreach (explode('.', $path) as $k) {
                if (!is_array($value) || !array_key_exists($k, $value)) {
                    return false;
                }
                $value = $value[$k];
            }
            if (!is_scalar($value)) {
                return false;
            }
            $concat .= (string) $value;
        }
        $expected = hash('sha256', $concat . $timestamp . $secret);
        return hash_equals($expected, strtolower($checksum));
    }
}
