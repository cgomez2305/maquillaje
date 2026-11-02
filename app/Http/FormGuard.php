<?php
declare(strict_types=1);

namespace App\Http;

/**
 * Protección de formularios públicos (§14.3): honeypot, tiempo mínimo de llenado (firmado)
 * y 5 envíos por IP cada 10 minutos. Usar junto con spam_fields() en la vista.
 */
final class FormGuard
{
    /** Devuelve null si el envío es aceptable, o un mensaje de error genérico. */
    public static function check(Request $request, string $form): ?string
    {
        if (trim((string) ($request->post['website'] ?? '')) !== '') {
            return 'No pudimos procesar el formulario.';
        }
        $ts = (string) ($request->post['_ts'] ?? '');
        if (!preg_match('/^(\d{10})\.([a-f0-9]{64})$/', $ts, $m) || !hash_equals(hash_hmac('sha256', $m[1], app_key()), $m[2])) {
            return 'El formulario caducó. Recarga la página e inténtalo de nuevo.';
        }
        $elapsed = time() - (int) $m[1];
        if ($elapsed < (int) config('app.security.form_min_seconds', 3)) {
            return 'Revisa los datos y vuelve a enviar el formulario.';
        }
        if ($elapsed > 60 * 60 * 6) {
            return 'El formulario caducó. Recarga la página e inténtalo de nuevo.';
        }
        [$max, $window] = config('app.security.form_rate_limit', [5, 600]);
        if (!RateLimiter::hit('form:' . $form . ':' . $request->ip(), (int) $max, (int) $window)) {
            return 'Recibimos varios envíos seguidos. Espera unos minutos o escríbenos por WhatsApp.';
        }
        return null;
    }
}
