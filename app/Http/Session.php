<?php
declare(strict_types=1);

namespace App\Http;

/**
 * Sesión PHP con cookies endurecidas (§14.2). Arranque perezoso: el sitio público solo
 * abre sesión cuando hay un formulario (CSRF) o un flujo de reserva.
 * El panel usa otra cookie, SameSite=Strict y caducidad por inactividad y absoluta.
 */
final class Session
{
    private static bool $panel = false;
    private static bool $secure = true;

    public static function configure(Request $request): void
    {
        self::$panel = $request->isPanel();
        self::$secure = $request->isHttps() || config('app.env') === 'production';
    }

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        if (defined('TESTING')) {
            $_SESSION = $_SESSION ?? [];
            return;
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '6');
        ini_set('session.gc_maxlifetime', (string) (60 * 60 * 12));
        session_name(self::$panel ? 'mubf_panel' : 'mubf_s');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => self::$panel ? '/panel' : '/',
            'secure' => self::$secure,
            'httponly' => true,
            'samesite' => self::$panel ? 'Strict' : 'Lax',
        ]);
        $dir = BASE_PATH . '/storage/cache/sessions';
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        if (is_dir($dir) && is_writable($dir)) {
            session_save_path($dir);
        }
        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }

    /** Mensaje de un solo uso. */
    public static function flash(string $key, mixed $value = null): mixed
    {
        self::start();
        if (func_num_args() === 2) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        $v = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $v;
    }

    public static function regenerate(): void
    {
        self::start();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function destroy(): void
    {
        self::start();
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'],
                'httponly' => true, 'samesite' => $p['samesite'],
            ]);
            session_destroy();
        }
    }
}
