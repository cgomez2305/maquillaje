<?php
declare(strict_types=1);

/**
 * Pruebas de regresión de seguridad (auditoría §14). Contra la BD local:
 *   php tests/SecurityTest.php
 * Cubre: redirecciones abiertas, rutas con caracteres de control, análisis de la ruta, enumeración de
 * usuarios en el login, CSRF, autorización por rol en el servidor, tokens públicos, límite de eventos
 * falsos del webhook y tolerancia a avisos de obsolescencia de PHP.
 */

define('TESTING', true);
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Http\Csrf;
use App\Http\Request;
use App\Http\Response;
use App\Kernel;
use App\Panel\Auth;
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
foreach (['/tienda' => true, '/reserva/MBF-2611-0001?t=x' => true, 'https://wa.me/573161910936' => true, 'https://checkout.wompi.co/p/' => true,
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

// 4. Login: mismo mensaje y tiempo comparable para correo inexistente, contraseña errónea y cuenta bloqueada
$email = 'sec-test-' . bin2hex(random_bytes(3)) . '@example.com';
Db::run('INSERT INTO users (name, email, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)', ['Prueba Seguridad', $email, password_hash('clave-correcta-larga-1', PASSWORD_ARGON2ID), 'staff', now_utc(), now_utc()]);
$uid = (int) Db::value('SELECT id FROM users WHERE email = ?', [$email]);
$req = static fn(): Request => new Request('POST', '/panel/login', [], [], $srv(), []);
$t0 = microtime(true);
$mNo = Auth::attempt('no-existe-' . bin2hex(random_bytes(3)) . '@example.com', 'x', $req());
$tNo = microtime(true) - $t0;
$t0 = microtime(true);
$mBad = Auth::attempt($email, 'incorrecta', $req());
$tBad = microtime(true) - $t0;
$check('mensaje idéntico: correo inexistente y contraseña errónea', $mNo !== null && $mNo === $mBad);
$check('correo inexistente también ejecuta Argon2id (tiempo > 40 % del real)', $tNo > 0.4 * $tBad && $tNo > 0.02);
Db::run('UPDATE users SET locked_until = ? WHERE id = ?', [gmdate('Y-m-d H:i:s', time() + 600), $uid]);
$mLocked = Auth::attempt($email, 'clave-correcta-larga-1', $req());
$check('cuenta bloqueada: mismo mensaje genérico (no revela que existe)', $mLocked === $mNo);
Db::run('UPDATE users SET locked_until = NULL, failed_logins = 0, active = 0 WHERE id = ?', [$uid]);
$check('usuario inactivo no entra', Auth::attempt($email, 'clave-correcta-larga-1', $req()) === $mNo);

// 5. CSRF: un POST sin token se rechaza (419)
$_SESSION = [];
$r = Kernel::handle(new Request('POST', '/estudio-maquillaje-floridablanca/cotizar', [], ['nombre' => 'x'], $srv(), []));
$check('POST público sin CSRF → 419', $r->status() === 419);
$r = Kernel::handle(new Request('POST', '/panel/login', [], ['email' => 'a@b.co', 'password' => 'x', '_token' => 'falso'], $srv(), []));
$check('POST del panel con CSRF falso → 419', $r->status() === 419);

// 6. Autorización por rol en el servidor (petición directa)
$asRole = static function (string $role) use ($srv): array {
    $id = (int) Db::value('SELECT id FROM users WHERE role = ? AND active = 1 ORDER BY id LIMIT 1', [$role]);
    $_SESSION = ['uid' => $id, '2fa_ok' => true, 'login_at' => time(), 'seen_at' => time()];
    Auth::reset();
    return ['_token' => Csrf::token()];
};
$tok = $asRole('partner');
foreach ([['POST', '/panel/ajustes'], ['GET', '/panel/ajustes'], ['POST', '/panel/reservas/nueva'], ['POST', '/panel/clientas/1/anonimizar'], ['POST', '/panel/ventas/canjear'], ['POST', '/panel/reservas/MBF-2611-0001/estado'], ['GET', '/panel/diario']] as [$m, $p]) {
    $r = Kernel::handle(new Request($m, $p, [], $tok, $srv(), []));
    $check("socio {$m} {$p} → 403", $r->status() === 403);
}
$tok = $asRole('staff');
foreach ([['GET', '/panel'], ['GET', '/panel/comisiones'], ['GET', '/panel/historial'], ['GET', '/panel/clientas'], ['POST', '/panel/comisiones/1/aprobar']] as [$m, $p]) {
    $r = Kernel::handle(new Request($m, $p, [], $tok, $srv(), []));
    $check("asistente {$m} {$p} → 403", $r->status() === 403);
}
$_SESSION = [];
Auth::reset();
$r = $get('/panel/reservas');
$check('sin sesión → redirección al login', $r->status() === 303 && str_starts_with($r->headers()['Location'] ?? '', '/panel/login'));
$_SESSION = ['uid' => $uid, '2fa_ok' => false, 'login_at' => time(), 'seen_at' => time()];
Auth::reset();
$r = $get('/panel/reservas');
$check('contraseña sin 2FA completado → no entra', $r->status() === 303);
$_SESSION = [];
Auth::reset();

// 7. Tokens públicos: código válido con token incorrecto o ausente → 404
$b = Db::one('SELECT code FROM bookings ORDER BY id LIMIT 1');
if ($b !== null) {
    $check('estado de reserva sin token → 404', $get('/reserva/' . $b['code'])->status() === 404);
    $check('estado de reserva con token incorrecto → 404', $get('/reserva/' . $b['code'], ['t' => str_repeat('A', 43)])->status() === 404);
    $check('estado de reserva con token como arreglo → 404', $get('/reserva/' . $b['code'], ['t' => ['x']])->status() === 404);
}
$check('encuesta con token inexistente → 404', $get('/encuesta/' . str_repeat('a', 43))->status() === 404);

// 8. Webhook: los eventos con firma inválida no pueden llenar payment_events
$ip = '10.77.' . random_int(0, 255) . '.' . random_int(1, 254);
$before = (int) Db::value('SELECT COUNT(*) FROM payment_events WHERE signature_valid = 0');
$codes = [];
for ($i = 0; $i < 23; $i++) {
    $res = Kernel::handle(new Request('POST', '/webhooks/wompi', [], [], ['REMOTE_ADDR' => $ip], [], [], json_encode(['event' => 'transaction.updated', 'data' => ['transaction' => ['id' => 'x', 'status' => 'APPROVED']], 'signature' => ['properties' => ['transaction.id'], 'checksum' => 'abc'], 'timestamp' => 1])));
    $codes[] = $res->status();
}
$added = (int) Db::value('SELECT COUNT(*) FROM payment_events WHERE signature_valid = 0') - $before;
$check('webhook con firma inválida → 401 y luego 429', $codes[0] === 401 && end($codes) === 429);
$check('como máximo 20 eventos inválidos por IP y hora', $added <= 20);
$raw = (string) Db::value('SELECT raw FROM payment_events WHERE signature_valid = 0 ORDER BY id DESC LIMIT 1');
$check('de un evento inválido solo se guarda un resumen', str_contains($raw, '"invalid"') && !str_contains($raw, 'checksum'));

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

// 9b. Cambio de contraseña: las demás sesiones de la cuenta dejan de valer; la actual se conserva
Db::run('UPDATE users SET active = 1, sessions_valid_after = NULL WHERE id = ?', [$uid]);
$_SESSION = ['uid' => $uid, '2fa_ok' => true, 'login_at' => time() - 120, 'auth_at' => time() - 120, 'seen_at' => time()];
Auth::reset();
$check('sesión válida antes del cambio de contraseña', Auth::user() !== null);
$other = $_SESSION;
Auth::invalidateOtherSessions($uid, true);
Auth::reset();
$check('la sesión que cambió la contraseña sigue activa', Auth::user() !== null);
$_SESSION = $other; // otra sesión abierta antes del cambio (otro dispositivo)
Auth::reset();
$check('otra sesión anterior al cambio queda cerrada', Auth::user() === null);
$_SESSION = ['uid' => $uid, '2fa_ok' => true, 'login_at' => time() - 120, 'seen_at' => time()];
Auth::reset();
$check('una sesión sin sello de autenticación tampoco vale tras el cambio', Auth::user() === null);
$_SESSION = [];
Auth::reset();
Db::run('UPDATE users SET active = 0 WHERE id = ?', [$uid]);

// 10. Cabeceras de seguridad presentes en una respuesta normal
$h = $get('/')->headers();
$csp = $h['Content-Security-Policy'] ?? '';
$check('CSP sin unsafe-inline y con nonce', $csp !== '' && !str_contains($csp, 'unsafe-inline') && str_contains($csp, "'nonce-"));
$check("CSP frame-ancestors 'none' y object-src 'none'", str_contains($csp, "frame-ancestors 'none'") && str_contains($csp, "object-src 'none'"));
foreach (['X-Content-Type-Options' => 'nosniff', 'X-Frame-Options' => 'DENY', 'Referrer-Policy' => 'strict-origin-when-cross-origin', 'Cross-Origin-Opener-Policy' => 'same-origin'] as $k => $v) {
    $check("cabecera {$k}", ($h[$k] ?? '') === $v);
}

echo $fails === 0 ? "\nTodas las pruebas de seguridad pasaron.\n" : "\n{$fails} prueba(s) de seguridad fallaron.\n";
exit($fails === 0 ? 0 : 1);
