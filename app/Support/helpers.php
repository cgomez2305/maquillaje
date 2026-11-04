<?php
declare(strict_types=1);

/**
 * Funciones de ayuda globales. Las vistas solo usan estas funciones, bucles y escape.
 */

use App\Http\Csrf;
use App\Http\SecurityHeaders;
use App\Support\Config;
use App\Support\Env;

if (!function_exists('e')) {
    /** Escapa cualquier salida HTML. */
    function e(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

function env(string $key, ?string $default = null): ?string
{
    return Env::get($key, $default);
}

/** 200000 → «200.000 COP» */
function money(int $amount, bool $suffix = true): string
{
    $sign = $amount < 0 ? '−' : '';
    return $sign . number_format(abs($amount), 0, ',', '.') . ($suffix ? ' COP' : '');
}

/** URL absoluta del sitio para una ruta interna. */
function url(string $path = '/'): string
{
    $path = '/' . ltrim($path, '/');
    return config('app.url') . $path;
}

/** Ruta de un recurso de /public/assets con versión por hash de contenido. */
function asset(string $path): string
{
    static $cache = [];
    $path = ltrim($path, '/');
    if (!isset($cache[$path])) {
        $file = BASE_PATH . '/public/assets/' . $path;
        $cache[$path] = is_file($file) ? substr(hash_file('sha256', $file), 0, 10) : '0';
    }
    return '/assets/' . $path . '?v=' . $cache[$path];
}

function csp_nonce(): string
{
    return SecurityHeaders::nonce();
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(Csrf::token()) . '">';
}

function app_key(): string
{
    static $key = null;
    if ($key === null) {
        $raw = (string) config('app.key');
        $decoded = base64_decode(str_starts_with($raw, 'base64:') ? substr($raw, 7) : $raw, true);
        $key = ($decoded !== false && strlen($decoded) >= 32) ? substr($decoded, 0, 32) : hash('sha256', 'mubf-dev-key|' . BASE_PATH, true);
    }
    return $key;
}

/** +573161910936 → «+57 316 191 0936» */
function phone_display(string $e164): string
{
    if (preg_match('/^\+57(\d{3})(\d{3})(\d{4})$/', $e164, $m)) {
        return "+57 {$m[1]} {$m[2]} {$m[3]}";
    }
    return $e164;
}

/** Enmascarado para el socio: «+57 300 ••• 4412» */
function mask_phone(?string $e164): string
{
    if (!$e164) {
        return '—';
    }
    if (preg_match('/^\+57(\d{3})\d{3}(\d{4})$/', $e164, $m)) {
        return "+57 {$m[1]} ••• {$m[2]}";
    }
    return substr($e164, 0, 4) . ' ••• ' . substr($e164, -4);
}

function mask_email(?string $email): string
{
    if (!$email || !str_contains($email, '@')) {
        return '—';
    }
    [$user, $domain] = explode('@', $email, 2);
    return mb_substr($user, 0, 1) . '•••@' . $domain;
}

/** Fecha/hora UTC de la BD → DateTimeImmutable en Bogotá. */
function local_time(string|DateTimeInterface|null $utc): ?DateTimeImmutable
{
    if ($utc === null || $utc === '') {
        return null;
    }
    $tz = new DateTimeZone(config('app.timezone', 'America/Bogota'));
    if ($utc instanceof DateTimeInterface) {
        return DateTimeImmutable::createFromInterface($utc)->setTimezone($tz);
    }
    return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))->setTimezone($tz);
}

function now_utc(): string
{
    return gmdate('Y-m-d H:i:s');
}

/** Fecha legible en español: «sábado 14 de noviembre de 2026». */
function fecha_larga(DateTimeInterface $d, bool $weekday = true): string
{
    static $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $s = (int) $d->format('j') . ' de ' . mes_nombre((int) $d->format('n')) . ' de ' . $d->format('Y');
    return $weekday ? $dias[(int) $d->format('w')] . ' ' . $s : $s;
}

function fecha_corta(DateTimeInterface $d): string
{
    return (int) $d->format('j') . ' ' . mb_substr(mes_nombre((int) $d->format('n')), 0, 3) . ' ' . $d->format('Y');
}

function mes_nombre(int $m): string
{
    return ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'][$m - 1] ?? '';
}

/** «9:00 a. m.» */
function hora(DateTimeInterface $d): string
{
    $h = (int) $d->format('G');
    $suffix = $h < 12 ? 'a. m.' : 'p. m.';
    $h12 = $h % 12 === 0 ? 12 : $h % 12;
    return $h12 . ':' . $d->format('i') . ' ' . $suffix;
}

/**
 * Enlace de WhatsApp medido: /wa crea un lead con código WEB-AAMM-NNNN y redirige a wa.me
 * con el mensaje prellenado que incluye el código (§9.3).
 */
function wa_href(string $origin = 'general', ?string $topic = null): string
{
    $q = ['o' => $origin];
    if ($topic !== null) {
        $q['t'] = $topic;
    }
    return '/wa?' . http_build_query($q);
}

/** URL directa de wa.me con texto (sin medición), para correos y confirmaciones. */
function wa_direct(string $text): string
{
    return 'https://wa.me/' . config('app.business.whatsapp_number') . '?text=' . rawurlencode($text);
}

/**
 * <picture> responsive a partir de public/assets/img/manifest.json.
 *
 * @param array{class?:string,sizes?:string,eager?:bool,priority?:bool,width?:int,attrs?:string} $o
 */
function img(string $key, string $alt, array $o = []): string
{
    static $manifest = null;
    if ($manifest === null) {
        $file = BASE_PATH . '/public/assets/img/manifest.json';
        $manifest = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
    }
    $m = $manifest[$key] ?? null;
    if ($m === null) {
        return '<img src="" alt="' . e($alt) . '">';
    }
    $sizes = $o['sizes'] ?? '100vw';
    $base = '/assets/img/' . $key . '-';
    $widths = $m['widths'];
    $set = static fn(string $ext): string => implode(', ', array_map(static fn(int $w): string => $base . $w . '.' . $ext . ' ' . $w . 'w', $widths));
    $fallbackW = (int) ($o['width'] ?? $widths[min(1, count($widths) - 1)]);
    if (!in_array($fallbackW, $widths, true)) {
        $fallbackW = end($widths);
    }
    $loading = !empty($o['eager']) || !empty($o['priority']) ? 'eager' : 'lazy';
    $fp = !empty($o['priority']) ? ' fetchpriority="high"' : '';
    $class = isset($o['class']) ? ' class="' . e($o['class']) . '"' : '';
    $extra = $o['attrs'] ?? '';
    $html = '<picture>';
    if ($m['ext'] === 'jpg') {
        $html .= '<source type="image/avif" srcset="' . $set('avif') . '" sizes="' . e($sizes) . '">';
    }
    $html .= '<source type="image/webp" srcset="' . $set('webp') . '" sizes="' . e($sizes) . '">';
    $html .= '<img src="' . $base . $fallbackW . '.' . $m['ext'] . '" srcset="' . $set($m['ext']) . '" sizes="' . e($sizes) . '"'
        . ' width="' . (int) $m['w'] . '" height="' . (int) $m['h'] . '" alt="' . e($alt) . '"'
        . ' loading="' . $loading . '"' . (!empty($o['priority']) ? '' : ' decoding="async"') . $fp . $class . $extra . '>';
    return $html . '</picture>';
}

/** Ruta simple a una variante concreta (para logos pequeños y Open Graph). */
function img_src(string $key, int $width, string $ext = 'png'): string
{
    return '/assets/img/' . $key . '-' . $width . '.' . $ext;
}

/** Iconos SVG en línea (trazo), decorativos por defecto. */
function icon(string $name, string $class = 'ic'): string
{
    $paths = [
        'arrow' => ['0 0 16 16', '<path d="M2.5 8h11M9.5 4l4 4-4 4"/>', '1.2'],
        'arrow-left' => ['0 0 16 16', '<path d="M13.5 8h-11M6.5 4l-4 4 4 4"/>', '1.2'],
        'plus' => ['0 0 28 28', '<path d="M14 6v16M6 14h16"/>', '1.2'],
        'menu' => ['0 0 24 24', '<path d="M4 8h16M4 16h16"/>', '1.4'],
        'close' => ['0 0 24 24', '<path d="M6 6l12 12M18 6L6 18"/>', '1.4'],
        // Globo de conversación con auricular: lectura inequívoca de WhatsApp a 18–24 px
        'whatsapp' => ['0 0 24 24', '<path d="M7.9 20A9 9 0 1 0 4 16.1L2.6 21.4z"/><path fill="currentColor" stroke="none" transform="translate(7.3 7.3) scale(.39)" d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>', '1.5'],
        'check' => ['0 0 18 18', '<circle cx="9" cy="9" r="8.4"/><path d="M5.5 9.2l2.3 2.3 4.7-4.9"/>', '1.1'],
        'pin' => ['0 0 24 24', '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>', '1.4'],
        'mail' => ['0 0 24 24', '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3.5 6.5l8.5 6.5 8.5-6.5"/>', '1.4'],
        'clock' => ['0 0 24 24', '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>', '1.4'],
        'instagram' => ['0 0 24 24', '<rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r=".6" fill="currentColor"/>', '1.4'],
        'facebook' => ['0 0 24 24', '<path d="M15.5 3.5h-2a3.5 3.5 0 0 0-3.5 3.5v13.5M7 10h7.5"/>', '1.5'],
        'link' => ['0 0 24 24', '<path d="M10 14a4.5 4.5 0 0 0 6.4 0l3-3a4.5 4.5 0 0 0-6.4-6.4l-1 1"/><path d="M14 10a4.5 4.5 0 0 0-6.4 0l-3 3a4.5 4.5 0 0 0 6.4 6.4l1-1"/>', '1.4'],
        'chevron' => ['0 0 16 16', '<path d="M6 3.5L10.5 8 6 12.5"/>', '1.25'],
        'chevron-left' => ['0 0 16 16', '<path d="M10 3.5L5.5 8l4.5 4.5"/>', '1.25'],
        'home' => ['0 0 24 24', '<path d="M4 10.5L12 4l8 6.5V19a1 1 0 0 1-1 1h-4.5v-5.5h-5V20H5a1 1 0 0 1-1-1z"/>', '1.4'],
        'image' => ['0 0 24 24', '<rect x="3.5" y="4.5" width="17" height="15" rx="2.5"/><circle cx="9" cy="10" r="1.6"/><path d="M4 17.5l4.5-4.5 3.5 3.5 2.5-2.5 5.5 5.5"/>', '1.4'],
        'bag' => ['0 0 24 24', '<path d="M5.2 8h13.6l-1 11.1a1 1 0 0 1-1 .9H7.2a1 1 0 0 1-1-.9z"/><path d="M9 8V6.8a3 3 0 0 1 6 0V8"/>', '1.4'],
        'book' => ['0 0 24 24', '<path d="M12 6.5C10.2 5 7.6 4.5 4 4.8v13.4c3.6-.3 6.2.2 8 1.8 1.8-1.6 4.4-2.1 8-1.8V4.8c-3.6-.3-6.2.2-8 1.7z"/><path d="M12 6.5V20"/>', '1.4'],
        'tag' => ['0 0 24 24', '<path d="M3.8 12.4V4.8a1 1 0 0 1 1-1h7.6a1 1 0 0 1 .7.3l7 7a1 1 0 0 1 0 1.4l-7.6 7.6a1 1 0 0 1-1.4 0l-7-7a1 1 0 0 1-.3-.7z"/><circle cx="8.3" cy="8.3" r="1.5"/>', '1.4'],
        'calendar' => ['0 0 24 24', '<rect x="3.5" y="5" width="17" height="15" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>', '1.4'],
        'tiktok' => ['0 0 24 24', '<path d="M14 3.5v11.3a3.9 3.9 0 1 1-3.9-3.9"/><path d="M14 3.5c.4 2.6 2.4 4.5 5.2 4.7"/>', '1.5'],
    ];
    [$vb, $d, $sw] = $paths[$name] ?? $paths['arrow'];
    return '<svg class="' . e($class) . '" viewBox="' . $vb . '" fill="none" stroke="currentColor" stroke-width="' . $sw . '" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $d . '</svg>';
}

/** <script type="application/ld+json"> seguro (§14.3). */
function json_ld(array $data): string
{
    $json = json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    return '<script type="application/ld+json" nonce="' . e(csp_nonce()) . '">' . $json . '</script>';
}

/** Token aleatorio base64url (43 caracteres para 32 bytes). */
function random_token(int $bytes = 32): string
{
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
}

/** Atajo para registrar en storage/logs/app-AAAA-MM.log */
function app_log(string $level, string $message, array $context = []): void
{
    $line = sprintf("[%s] %s: %s %s\n", gmdate('c'), strtoupper($level), $message, $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : '');
    @file_put_contents(BASE_PATH . '/storage/logs/app-' . gmdate('Y-m') . '.log', $line, FILE_APPEND | LOCK_EX);
}

/** Etiqueta aria-current para la navegación. */
function current_attr(string $path, string $current): string
{
    $match = $path === '/' ? $current === '/' : ($current === $path || str_starts_with($current, $path . '/'));
    return $match ? ' aria-current="page"' : '';
}
