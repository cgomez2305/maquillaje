<?php
declare(strict_types=1);

/**
 * Arranque común para web y consola (cron): autoload, entorno, configuración,
 * zona horaria y manejo de errores. No envía salida.
 */

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (is_file(BASE_PATH . '/vendor/autoload.php')) {
    require BASE_PATH . '/vendor/autoload.php';
} else {
    // Respaldo si aún no se ejecutó composer install (PHPMailer no estará disponible)
    spl_autoload_register(static function (string $class): void {
        if (str_starts_with($class, 'App\\')) {
            $file = BASE_PATH . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
            if (is_file($file)) {
                require $file;
            }
        }
    });
    require BASE_PATH . '/app/Support/helpers.php';
}

App\Support\Env::load(BASE_PATH . '/.env');
App\Support\Config::load(BASE_PATH . '/config');

// La base de datos guarda UTC; la presentación usa America/Bogota (helpers local_time()).
date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

ini_set('display_errors', config('app.debug') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', BASE_PATH . '/storage/logs/php-' . gmdate('Y-m') . '.log');
error_reporting(E_ALL);
ini_set('expose_php', '0');

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    if ($severity & (E_DEPRECATED | E_USER_DEPRECATED)) {
        error_log("Obsoleto: {$message} en {$file}:{$line}");
        return true; // una actualización de PHP no debe tumbar el sitio
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
