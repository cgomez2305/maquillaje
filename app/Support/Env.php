<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Cargador mínimo de .env (CLAVE=valor, comentarios con #, comillas opcionales).
 * Las variables reales del entorno tienen prioridad sobre el archivo.
 */
final class Env
{
    /** @var array<string,string> */
    private static array $vars = [];

    public static function load(string $file): void
    {
        if (!is_file($file) || !is_readable($file)) {
            return;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = array_map('trim', explode('=', $line, 2));
            if (!preg_match('/^[A-Z][A-Z0-9_]*$/', $key)) {
                continue;
            }
            if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                $q = $value[0];
                $end = strpos($value, $q, 1);
                $value = $end === false ? substr($value, 1) : substr($value, 1, $end - 1);
            } else {
                $value = trim(preg_replace('/\s+#.*$/', '', $value) ?? '');
            }
            self::$vars[$key] = $value;
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $real = getenv($key);
        if ($real !== false && $real !== '') {
            return $real;
        }
        return self::$vars[$key] ?? $default;
    }

    public static function set(string $key, string $value): void
    {
        self::$vars[$key] = $value;
    }
}
