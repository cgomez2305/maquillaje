<?php
declare(strict_types=1);

namespace App\Panel;

use App\Domain\Audit\AuditLog;
use App\Http\RateLimiter;
use App\Http\Request;
use App\Http\Session;
use App\Support\Crypto;
use App\Support\Db;
use App\Support\Totp;

/**
 * Autenticación del panel (§14.2): Argon2id, bloqueo progresivo por cuenta y por IP,
 * 2FA TOTP obligatorio para dueña y socio, códigos de respaldo de un solo uso,
 * caducidad por inactividad (30 min) y absoluta (12 h).
 */
final class Auth
{
    private static ?array $user = null;

    public const ROLE_LABELS = ['owner' => 'Dueña · administración', 'partner' => 'Socio · solo lectura', 'staff' => 'Asistente'];

    /** @return array<string,mixed>|null usuario autenticado y con 2FA completado */
    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        $id = Session::get('uid');
        if (!is_int($id) || Session::get('2fa_ok') !== true) {
            return null;
        }
        $u = Db::one('SELECT id, name, email, role, totp_enabled, active FROM users WHERE id = ?', [$id]);
        if ($u === null || !(int) $u['active']) {
            return null;
        }
        return self::$user = $u;
    }

    public static function id(): ?int
    {
        return isset(self::user()['id']) ? (int) self::user()['id'] : null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function is(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    /** El socio ve teléfonos y correos enmascarados (§14.5). */
    public static function phone(?string $e164): string
    {
        if ($e164 === null || str_starts_with($e164, 'anon:')) {
            return '—';
        }
        return self::is('partner') ? mask_phone($e164) : phone_display($e164);
    }

    public static function email(?string $email): string
    {
        if ($email === null || $email === '') {
            return '—';
        }
        return self::is('partner') ? mask_email($email) : $email;
    }

    public static function requiresTotp(string $role): bool
    {
        return in_array($role, ['owner', 'partner'], true);
    }

    /**
     * Primer factor. Devuelve null si es correcto o un mensaje genérico.
     */
    public static function attempt(string $email, string $password, Request $request): ?string
    {
        $generic = 'Correo o contraseña incorrectos.';
        $ip = $request->ip();
        $email = mb_strtolower(trim($email));
        if (!RateLimiter::hit('login_ip:' . $ip, 20, 900)) {
            AuditLog::record(null, 'login_bloqueado_ip', $email, null, $ip);
            return 'Demasiados intentos desde esta conexión. Espera unos minutos.';
        }
        $u = Db::one('SELECT * FROM users WHERE email = ?', [$email]);
        $now = now_utc();
        if ($u !== null && $u['locked_until'] !== null && $u['locked_until'] > $now) {
            AuditLog::record((int) $u['id'], 'login_bloqueado', $email, null, $ip);
            return 'La cuenta está bloqueada temporalmente por intentos fallidos. Inténtalo más tarde.';
        }
        // Verificación de tiempo constante aun si el usuario no existe
        $hash = $u['password_hash'] ?? '$argon2id$v=19$m=65536,t=4,p=1$c29tZXNhbHRzb21lc2FsdA$Qe0x2a2x1a0b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8';
        $ok = password_verify($password, $hash) && $u !== null && (int) $u['active'] === 1;
        if (!$ok) {
            if ($u !== null) {
                $fails = (int) $u['failed_logins'] + 1;
                $lock = null;
                $max = (int) config('app.security.max_login_attempts', 5);
                if ($fails >= $max) {
                    // Espera exponencial: 1, 2, 4, 8… minutos (máx. 24 h)
                    $minutes = min(1440, 2 ** ($fails - $max));
                    $lock = gmdate('Y-m-d H:i:s', time() + 60 * $minutes);
                }
                Db::run('UPDATE users SET failed_logins = ?, locked_until = ?, updated_at = ? WHERE id = ?', [$fails, $lock, $now, (int) $u['id']]);
            }
            AuditLog::record($u ? (int) $u['id'] : null, 'login_fallido', $email, null, $ip);
            return $generic;
        }
        if (password_needs_rehash($u['password_hash'], PASSWORD_ARGON2ID)) {
            Db::run('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_ARGON2ID), (int) $u['id']]);
        }
        Db::run('UPDATE users SET failed_logins = 0, locked_until = NULL, updated_at = ? WHERE id = ?', [$now, (int) $u['id']]);
        Session::regenerate();
        Session::put('uid', (int) $u['id']);
        Session::put('2fa_ok', false);
        Session::put('login_at', time());
        Session::put('seen_at', time());
        if (!(int) $u['totp_enabled']) {
            if (self::requiresTotp($u['role'])) {
                // Debe configurar el 2FA antes de entrar: sesión limitada a /panel/seguridad
                Session::put('2fa_ok', true);
                Session::put('setup_only', true);
            } else {
                Session::put('2fa_ok', true);
                self::loggedIn((int) $u['id'], $ip);
            }
        }
        return null;
    }

    /** Segundo factor: código TOTP o código de respaldo. */
    public static function verifySecondFactor(string $code, Request $request): bool
    {
        $id = Session::get('uid');
        if (!is_int($id)) {
            return false;
        }
        if (!RateLimiter::hit('totp:' . $id, 6, 600)) {
            return false;
        }
        $u = Db::one('SELECT id, totp_secret, totp_last_step FROM users WHERE id = ?', [$id]);
        if ($u === null || $u['totp_secret'] === null) {
            return false;
        }
        $secret = Crypto::decrypt((string) $u['totp_secret']);
        $ok = false;
        if ($secret !== null) {
            $step = Totp::verify($secret, $code, $u['totp_last_step'] !== null ? (int) $u['totp_last_step'] : null);
            if ($step !== null) {
                Db::run('UPDATE users SET totp_last_step = ? WHERE id = ?', [$step, $id]);
                $ok = true;
            }
        }
        if (!$ok) {
            $ok = self::useBackupCode($id, $code);
        }
        if ($ok) {
            Session::regenerate();
            Session::put('2fa_ok', true);
            Session::put('setup_only', false);
            self::loggedIn($id, $request->ip());
        } else {
            AuditLog::record($id, '2fa_fallido', null, null, $request->ip());
        }
        return $ok;
    }

    public static function useBackupCode(int $userId, string $code): bool
    {
        $norm = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code) ?? '');
        if (strlen($norm) !== 10) {
            return false;
        }
        $hash = hash_hmac('sha256', $norm, app_key());
        $row = Db::one('SELECT id FROM user_backup_codes WHERE user_id = ? AND code_hash = ? AND used_at IS NULL', [$userId, $hash]);
        if ($row === null) {
            return false;
        }
        Db::run('UPDATE user_backup_codes SET used_at = ? WHERE id = ?', [now_utc(), (int) $row['id']]);
        AuditLog::record($userId, '2fa_codigo_respaldo_usado');
        return true;
    }

    /** @return list<string> 10 códigos nuevos (se muestran una sola vez) */
    public static function newBackupCodes(int $userId): array
    {
        Db::run('UPDATE user_backup_codes SET used_at = ? WHERE user_id = ? AND used_at IS NULL', [now_utc(), $userId]);
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $raw = strtoupper(substr(bin2hex(random_bytes(5)), 0, 10));
            $codes[] = substr($raw, 0, 5) . '-' . substr($raw, 5);
            Db::run('INSERT INTO user_backup_codes (user_id, code_hash, created_at) VALUES (?, ?, ?)', [$userId, hash_hmac('sha256', $raw, app_key()), now_utc()]);
        }
        return $codes;
    }

    public static function logout(Request $request): void
    {
        $id = Session::get('uid');
        if (is_int($id)) {
            AuditLog::record($id, 'logout', null, null, $request->ip());
        }
        Session::destroy();
        self::$user = null;
    }

    private static function loggedIn(int $id, string $ip): void
    {
        Db::run('UPDATE users SET last_login_at = ? WHERE id = ?', [now_utc(), $id]);
        AuditLog::record($id, 'login', null, null, $ip);
    }

    /** Para pruebas. */
    public static function reset(): void
    {
        self::$user = null;
    }
}
