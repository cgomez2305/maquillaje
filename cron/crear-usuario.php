<?php
declare(strict_types=1);

/**
 * Crea o reactiva un usuario del panel desde la consola (nunca desde la web, §16).
 *   php cron/crear-usuario.php --email=correo@dominio --nombre="María Paula Fletcher" --rol=owner
 * Roles: owner (dueña), partner (socio), staff (asistente). La contraseña se pide por consola
 * (o --password= solo en entornos de prueba). Dueña y socio configuran el 2FA en el primer acceso.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Domain\Audit\AuditLog;
use App\Support\Db;

$opts = getopt('', ['email:', 'nombre:', 'rol:', 'password::', 'reset-2fa']);
$email = mb_strtolower(trim((string) ($opts['email'] ?? '')));
$name = trim((string) ($opts['nombre'] ?? ''));
$role = (string) ($opts['rol'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '' || !in_array($role, ['owner', 'partner', 'staff'], true)) {
    fwrite(STDERR, "Uso: php cron/crear-usuario.php --email=… --nombre=\"…\" --rol=owner|partner|staff [--reset-2fa]\n");
    exit(1);
}

$password = $opts['password'] ?? null;
if (!is_string($password) || $password === '') {
    fwrite(STDOUT, 'Contraseña (mín. 12 caracteres): ');
    if (DIRECTORY_SEPARATOR === '/') {
        shell_exec('stty -echo');
    }
    $password = trim((string) fgets(STDIN));
    if (DIRECTORY_SEPARATOR === '/') {
        shell_exec('stty echo');
    }
    fwrite(STDOUT, "\n");
}
if (mb_strlen($password) < (int) config('app.security.password_min_length', 12)) {
    fwrite(STDERR, "La contraseña debe tener al menos 12 caracteres.\n");
    exit(1);
}

$now = now_utc();
$hash = password_hash($password, PASSWORD_ARGON2ID);
$existing = Db::one('SELECT id FROM users WHERE email = ?', [$email]);
if ($existing) {
    $sql = 'UPDATE users SET name = ?, role = ?, password_hash = ?, active = 1, failed_logins = 0, locked_until = NULL, updated_at = ?';
    // Cambiar la contraseña por consola cierra todas las sesiones abiertas de esa cuenta
    \App\Panel\Auth::invalidateOtherSessions((int) $existing['id']);
    $params = [$name, $role, $hash, $now];
    if (isset($opts['reset-2fa'])) {
        $sql .= ', totp_enabled = 0, totp_secret = NULL, totp_last_step = NULL';
    }
    Db::run($sql . ' WHERE id = ?', [...$params, (int) $existing['id']]);
    AuditLog::record(null, 'usuario_actualizado_consola', $email, ['rol' => $role, 'reset_2fa' => isset($opts['reset-2fa'])]);
    echo "Usuario actualizado: {$email} ({$role})\n";
} else {
    Db::run(
        'INSERT INTO users (name, email, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)',
        [$name, $email, $hash, $role, $now, $now],
    );
    AuditLog::record(null, 'usuario_creado_consola', $email, ['rol' => $role]);
    echo "Usuario creado: {$email} ({$role})\n";
}
