<?php
declare(strict_types=1);

namespace App\Domain\Attribution;

use App\Http\Request;
use App\Support\Db;

/**
 * Atribución a la web (§9.3).
 *
 * - Guarda UTM, gclid y referente externo en una cookie de primera parte firmada (30 días,
 *   SameSite=Lax, Secure) la primera vez que llegan; se copian a la clienta en su primer registro.
 * - Decide si una reserva nueva es atribuible y con qué origen. El resultado se guarda en la
 *   reserva al crearla y no se puede editar después.
 */
final class AttributionService
{
    private const UTM_KEYS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid'];

    public static function captureFromRequest(Request $request): void
    {
        $found = [];
        foreach (self::UTM_KEYS as $k) {
            $v = $request->query[$k] ?? null;
            if (is_string($v) && $v !== '') {
                $found[$k] = mb_substr(preg_replace('/[^\w.\-:|\/ ]/u', '', $v) ?? '', 0, 120);
            }
        }
        $ref = $request->referer();
        $ownHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $refHost = $ref !== '' ? (string) parse_url($ref, PHP_URL_HOST) : '';
        if ($found === [] && ($refHost === '' || $refHost === $ownHost)) {
            return;
        }
        if (self::current() !== null && $found === []) {
            return; // ya hay un origen guardado: el primer contacto manda
        }
        if ($refHost !== '' && $refHost !== $ownHost) {
            $found['referrer'] = mb_substr($refHost, 0, 120);
        }
        $found['landing'] = mb_substr($request->path, 0, 120);
        $found['at'] = gmdate('c');
        self::setCookie($found);
    }

    /** @return array<string,string>|null */
    public static function current(): ?array
    {
        $name = (string) config('app.attribution.cookie_name', 'mubf_src');
        $raw = $_COOKIE[$name] ?? null;
        if (!is_string($raw) || !str_contains($raw, '.')) {
            return null;
        }
        [$payload, $sig] = explode('.', $raw, 2);
        if (!hash_equals(hash_hmac('sha256', $payload, app_key()), $sig)) {
            return null;
        }
        $data = json_decode((string) base64_decode(strtr($payload, '-_', '+/'), true), true);
        return is_array($data) ? array_map('strval', $data) : null;
    }

    /** @param array<string,string> $data */
    private static function setCookie(array $data): void
    {
        if (headers_sent() || PHP_SAPI === 'cli') {
            return;
        }
        $payload = rtrim(strtr(base64_encode((string) json_encode($data)), '+/', '-_'), '=');
        $value = $payload . '.' . hash_hmac('sha256', $payload, app_key());
        $days = (int) config('app.attribution.cookie_days', 30);
        setcookie((string) config('app.attribution.cookie_name', 'mubf_src'), $value, [
            'expires' => time() + $days * 86400,
            'path' => '/',
            'secure' => config('app.env') === 'production' || (($_SERVER['HTTPS'] ?? '') === 'on'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE[(string) config('app.attribution.cookie_name', 'mubf_src')] = $value;
    }

    /** ¿La visita llegó desde la ficha de Google Business Profile? */
    public static function isGoogleBusiness(?array $utm): bool
    {
        return $utm !== null && strtolower($utm['utm_source'] ?? '') === 'gbp';
    }

    /**
     * Origen y atribución para una reserva creada por la clienta desde la web.
     *
     * @return array{source:string,is_web_attributed:int}
     */
    public static function forWebBooking(?array $utm): array
    {
        return [
            'source' => self::isGoogleBusiness($utm) ? Source::GOOGLE_BUSINESS->value : Source::WEB_FORM->value,
            'is_web_attributed' => 1,
        ];
    }

    /**
     * Origen y atribución para una reserva que la dueña registra a mano en el panel.
     * Busca el teléfono: si la clienta tiene ventana web vigente, la marca sola (no se puede desmarcar).
     *
     * @param string|null $leadCode código WEB-… que la clienta trajo en su mensaje de WhatsApp
     * @return array{source:string,is_web_attributed:int,lead_id:int|null}
     */
    public static function forManualBooking(?array $client, ?string $leadCode, string $nowUtc): array
    {
        if ($leadCode !== null && $leadCode !== '') {
            $lead = Db::one("SELECT id FROM leads WHERE code = ? AND kind IN ('whatsapp_click','cotizacion','guia_novias')", [strtoupper(trim($leadCode))]);
            if ($lead !== null) {
                return ['source' => Source::WHATSAPP_WEB->value, 'is_web_attributed' => 1, 'lead_id' => (int) $lead['id']];
            }
        }
        if ($client !== null && !empty($client['web_attributed_until']) && $client['web_attributed_until'] >= $nowUtc) {
            return ['source' => Source::WEB_RETURNING->value, 'is_web_attributed' => 1, 'lead_id' => null];
        }
        return ['source' => Source::MANUAL->value, 'is_web_attributed' => 0, 'lead_id' => null];
    }

    /** Fin de la ventana de atribución para una clienta cuyo primer contacto fue web. */
    public static function windowEnd(string $firstSeenUtc): string
    {
        $months = (int) config('app.attribution.window_months', 6);
        return (new \DateTimeImmutable($firstSeenUtc, new \DateTimeZone('UTC')))
            ->modify("+{$months} months")->format('Y-m-d H:i:s');
    }
}
