<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Configuración en config/*.php, accesible con notación de puntos: config('app.url').
 */
final class Config
{
    /** @var array<string,mixed> */
    private static array $items = [];
    private static string $dir = '';

    public static function load(string $dir): void
    {
        self::$dir = $dir;
        self::$items = [];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);
        if (!array_key_exists($file, self::$items)) {
            $path = self::$dir . '/' . $file . '.php';
            self::$items[$file] = (preg_match('/^[a-z_]+$/', $file) && is_file($path)) ? require $path : [];
        }
        $value = self::$items[$file];
        foreach ($parts as $p) {
            if (!is_array($value) || !array_key_exists($p, $value)) {
                return $default;
            }
            $value = $value[$p];
        }
        return $value;
    }

    /** Solo para pruebas. */
    public static function set(string $key, mixed $value): void
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);
        self::get($file);
        $ref = &self::$items[$file];
        foreach ($parts as $p) {
            if (!isset($ref[$p]) || !is_array($ref[$p])) {
                $ref[$p] = [];
            }
            $ref = &$ref[$p];
        }
        $ref = $value;
    }
}
