<?php
declare(strict_types=1);

/**
 * Arranque común de los cron: solo consola, bootstrap y bloqueo exclusivo con flock para que dos
 * ejecuciones del mismo trabajo nunca se solapen (si el anterior sigue corriendo, sale sin hacer nada).
 *
 * Las pruebas definen CRON_NO_RUN para cargar las funciones de cada script sin ejecutarlo.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

if (!defined('BASE_PATH')) {
    require dirname(__DIR__) . '/app/bootstrap.php';
}

/** @return resource|null el bloqueo (mantener la variable viva hasta el final) */
function cron_lock(string $name)
{
    $dir = BASE_PATH . '/storage/cache';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    $fh = fopen($dir . '/cron-' . preg_replace('/[^a-z0-9_-]/', '', $name) . '.lock', 'c');
    if ($fh === false || !flock($fh, LOCK_EX | LOCK_NB)) {
        fwrite(STDOUT, "[{$name}] otra ejecución sigue en curso; nada que hacer.\n");
        return null;
    }
    ftruncate($fh, 0);
    fwrite($fh, (string) getmypid());
    return $fh;
}

function cron_should_run(): bool
{
    return !defined('CRON_NO_RUN');
}

function cron_out(string $name, string $msg): void
{
    fwrite(STDOUT, sprintf("[%s] %s %s\n", gmdate('Y-m-d H:i:s'), $name, $msg));
    app_log('info', "cron {$name}: {$msg}");
}
