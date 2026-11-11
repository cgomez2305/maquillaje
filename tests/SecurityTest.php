<?php
declare(strict_types=1);

/**
 * Pruebas de regresión de seguridad (auditoría §14). Contra la BD local:
 *   php tests/SecurityTest.php
 * Cubre: redirecciones abiertas, rutas con caracteres de control, análisis de la ruta, CSRF, rutas retiradas
 * (panel, pagos, reservas en BD), solicitud por WhatsApp sin datos en el servidor y cabeceras de seguridad.
 */

define('TESTING', true);
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Http\Csrf;
use App\Http\Request;
use App\Http\Response;
use App\Kernel;
use App\Support\Db;

if (config('app.env') === 'production') {
    fwrite(STDERR, "No se ejecuta en producción.\n");
    exit(1);
}
App\Support\Env::set('MAIL_DRIVER', 'log');

$fails = 0;
$check = static function (string $name, bool $ok) use (&$fails): void {
    echo ($ok ? 'OK    ' : 'FALLO ') . $name . "\n";
    if (!$ok) {
        $fails++;
    }
};
$srv = static fn(array $extra = []): array => $extra + ['REMOTE_ADDR' => '10.9.' . random_int(0, 255) . '.' . random_int(1, 254), 'HTTP_USER_AGENT' => 'SecurityTest'];
$get = static fn(string $path, array $q = []): App\Http\Response => Kernel::handle(new Request('GET', $path, $q, [], $srv(), []));

// 1. Redirecciones abiertas
foreach (['/tienda' => true, '/reservar' => true, 'https://wa.me/573161910936' => true, 'https://checkout.wompi.co/p/' => false,
    '//evil.com' => false, '/\\evil.com' => false, "/\t/evil.com" => false, "/\n/evil.com" => false, ' /x' => false,
    'https://evil.com' => false, 'http://wa.me/x' => false, 'javascript:alert(1)' => false, '' => false] as $to => $allowed) {
    $check('isAllowedTarget(' . json_encode($to) . ') = ' . ($allowed ? 'sí' : 'no'), Response::isAllowedTarget($to) === $allowed);
}
$check('redirect() a destino no permitido cae en /', Response::redirect("/\t/evil.com")->headers()['Location'] === '/');

// 2. Rutas con caracteres de control o barras invertidas → 404 (nunca redirección)
foreach (["/\t/evil.com/", "/x\r\nX-Inj: 1/", "/tienda\\..\\.env", "/a\x00b"] as $p) {
    $r = $get($p);
    $check('ruta ' . json_encode($p) . ' → 404 sin Location', $r->status() === 404 && !isset($r->headers()['Location']));
}

// 3. Análisis de la ruta: «:dígitos» no cae en la portada
$_SERVER['REQUEST_URI'] = '/x:1/';
$check('Request::capture conserva «/x:1/»', Request::capture()->path === '/x:1/');
$_SERVER['REQUEST_URI'] = '/tienda?x=1#y';
$check('Request::capture separa la consulta', Request::capture()->path === '/tienda');
$_SERVER['REQUEST_URI'] = '/caf%C3%A9';
$check('Request::capture decodifica la ruta', Request::capture()->path === '/café');

// 5. CSRF: un POST sin token se rechaza (419)
$_SESSION = [];
$r = Kernel::handle(new Request('POST', '/estudio-maquillaje-floridablanca/cotizar', [], ['nombre' => 'x'], $srv(), []));
$check('POST público sin CSRF → 419', $r->status() === 419);

// 6. Rutas retiradas: panel, pagos en línea, estado de reservas y encuestas ya no existen
foreach ([['GET', '/panel'], ['GET', '/panel/login'], ['POST', '/webhooks/wompi'], ['GET', '/reserva/MBF-2611-0001'], ['GET', '/encuesta/' . str_repeat('a', 43)], ['GET', '/reservar/disponibilidad']] as [$m, $p]) {
    $r = Kernel::handle(new Request($m, $p, [], [], $srv(), []));
    $check("{$m} {$p} → 404", $r->status() === 404);
}
$r = $get('/tienda/bono-maquillaje-social');
$check('producto de la tienda → 301 a /tienda (próximamente)', $r->status() === 301 && ($r->headers()['Location'] ?? '') === '/tienda');

// 7. Solicitud por WhatsApp: redirige a wa.me con el mensaje y no guarda datos personales
$_SESSION = [];
$tok = Csrf::token();
$before = (int) Db::value('SELECT COUNT(*) FROM leads');
$r = Kernel::handle(new Request('POST', '/reservar', [], ['_token' => $tok, 'service' => 'pack3', 'people' => '2', 'date' => gmdate('Y-m-d', time() + 86400 * 10), 'time' => '09:00', 'location' => 'home', 'name' => 'Ana Prueba', 'town' => 'Bucaramanga', 'zone' => 'Cabecera', 'event' => 'boda', 'notes' => 'Alergia al látex'], $srv(), []));
$loc = $r->headers()['Location'] ?? '';
$text = rawurldecode((string) parse_url($loc, PHP_URL_QUERY));
$check('solicitud válida → 303 a wa.me', $r->status() === 303 && str_starts_with($loc, 'https://wa.me/'));
$check('el mensaje lleva servicio, personas, nombre y nota', str_contains($text, 'Novias y quinceañeras') && str_contains($text, 'Personas: 2') && str_contains($text, 'Ana Prueba') && str_contains($text, 'Alergia al látex'));
$check('el domicilio se suma al total según el municipio', str_contains($text, 'Domicilio: 30.000 COP (adicional)') && str_contains($text, 'Total estimado: 1.230.000 COP con domicilio'));
$check('no se guarda nada en la BD', (int) Db::value('SELECT COUNT(*) FROM leads') === $before);
$r = Kernel::handle(new Request('POST', '/reservar', [], ['_token' => $tok, 'service' => 'pack9', 'name' => ''], $srv(), []));
$check('solicitud incompleta → se queda en el formulario (422)', $r->status() === 422);

// 9. Avisos de obsolescencia (p. ej. al actualizar PHP) no tumban la petición
$ok = true;
try {
    trigger_error('prueba de obsolescencia', E_USER_DEPRECATED);
} catch (Throwable) {
    $ok = false;
}
$check('E_USER_DEPRECATED se registra sin excepción', $ok);
$threw = false;
try {
    trigger_error('prueba de advertencia', E_USER_WARNING);
} catch (ErrorException) {
    $threw = true;
}
$check('las advertencias siguen siendo excepciones', $threw);

// 10. Cabeceras de seguridad presentes en una respuesta normal
$h = $get('/')->headers();
$csp = $h['Content-Security-Policy'] ?? '';
$check('CSP sin unsafe-inline y con nonce', $csp !== '' && !str_contains($csp, 'unsafe-inline') && str_contains($csp, "'nonce-"));
$check("CSP frame-ancestors 'none' y object-src 'none'", str_contains($csp, "frame-ancestors 'none'") && str_contains($csp, "object-src 'none'"));
$check('CSP sin dominios de pago', !str_contains($csp, 'wompi'));
foreach (['X-Content-Type-Options' => 'nosniff', 'X-Frame-Options' => 'DENY', 'Referrer-Policy' => 'strict-origin-when-cross-origin', 'Cross-Origin-Opener-Policy' => 'same-origin'] as $k => $v) {
    $check("cabecera {$k}", ($h[$k] ?? '') === $v);
}

echo $fails === 0 ? "\nTodas las pruebas de seguridad pasaron.\n" : "\n{$fails} prueba(s) de seguridad fallaron.\n";
exit($fails === 0 ? 0 : 1);
