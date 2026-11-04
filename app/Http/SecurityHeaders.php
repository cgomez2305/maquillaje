<?php
declare(strict_types=1);

namespace App\Http;

/**
 * Cabeceras de seguridad y CSP con nonce por petición (§14.1).
 * No se permiten estilos ni scripts en línea sin nonce: las vistas no usan atributos style="".
 */
final class SecurityHeaders
{
    private static ?string $nonce = null;

    public static function nonce(): string
    {
        return self::$nonce ??= rtrim(strtr(base64_encode(random_bytes(18)), '+/', '-_'), '=');
    }

    public static function apply(Response $response, Request $request): Response
    {
        $n = self::nonce();
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$n}' https://www.googletagmanager.com",
            "style-src 'self' 'nonce-{$n}'",
            "img-src 'self' data: https:",
            "font-src 'self'",
            "frame-src https://www.google.com https://www.googletagmanager.com",
            "connect-src 'self' https://*.google-analytics.com https://*.analytics.google.com https://www.googletagmanager.com",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
            "form-action 'self' https://wa.me https://api.whatsapp.com",
        ]) . ($request->isHttps() ? '; upgrade-insecure-requests' : '');

        $response
            ->header('Content-Security-Policy', $csp)
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->header('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()')
            ->header('Cross-Origin-Opener-Policy', 'same-origin')
            ->header('X-Frame-Options', 'DENY');

        if ($request->isHttps()) {
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        return $response;
    }
}
