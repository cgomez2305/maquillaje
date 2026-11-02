<?php
declare(strict_types=1);

namespace App\Http;

use App\Support\Db;

/**
 * Límite de peticiones por cubeta (tabla rate_limits). Si la BD no está disponible,
 * usa archivos en storage/cache/ratelimit.
 */
final class RateLimiter
{
    /** Registra un intento y devuelve true si sigue dentro del límite. */
    public static function hit(string $bucket, int $max, int $windowSeconds): bool
    {
        $bucket = self::key($bucket);
        try {
            return Db::transaction(static function () use ($bucket, $max, $windowSeconds): bool {
                $now = gmdate('Y-m-d H:i:s');
                $row = Db::one('SELECT hits, reset_at FROM rate_limits WHERE bucket = ? FOR UPDATE', [$bucket]);
                if ($row === null || $row['reset_at'] <= $now) {
                    Db::run(
                        'REPLACE INTO rate_limits (bucket, hits, reset_at) VALUES (?, 1, ?)',
                        [$bucket, gmdate('Y-m-d H:i:s', time() + $windowSeconds)],
                    );
                    return true;
                }
                Db::run('UPDATE rate_limits SET hits = hits + 1 WHERE bucket = ?', [$bucket]);
                return ((int) $row['hits'] + 1) <= $max;
            });
        } catch (\Throwable) {
            return self::fileHit($bucket, $max, $windowSeconds);
        }
    }

    public static function tooMany(string $bucket, int $max): bool
    {
        $bucket = self::key($bucket);
        try {
            $row = Db::one('SELECT hits, reset_at FROM rate_limits WHERE bucket = ?', [$bucket]);
            return $row !== null && $row['reset_at'] > gmdate('Y-m-d H:i:s') && (int) $row['hits'] >= $max;
        } catch (\Throwable) {
            return false;
        }
    }

    public static function clear(string $bucket): void
    {
        $bucket = self::key($bucket);
        try {
            Db::run('DELETE FROM rate_limits WHERE bucket = ?', [$bucket]);
        } catch (\Throwable) {
        }
    }

    /** Clave corta: prefijo legible («login», «form»…) + hash del resto (no guarda IP en claro). */
    private static function key(string $bucket): string
    {
        $prefix = substr(preg_replace('/[^a-z_]/', '', strtolower(explode(':', $bucket)[0])) ?? '', 0, 30);
        return $prefix . ':' . substr(hash('sha256', $bucket), 0, 64);
    }

    private static function fileHit(string $bucket, int $max, int $window): bool
    {
        $dir = BASE_PATH . '/storage/cache/ratelimit';
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
        $file = $dir . '/' . hash('sha256', $bucket);
        $fh = @fopen($file, 'c+');
        if ($fh === false) {
            return true;
        }
        flock($fh, LOCK_EX);
        $data = json_decode((string) stream_get_contents($fh), true) ?: ['hits' => 0, 'reset' => 0];
        if ($data['reset'] <= time()) {
            $data = ['hits' => 0, 'reset' => time() + $window];
        }
        $data['hits']++;
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, (string) json_encode($data));
        flock($fh, LOCK_UN);
        fclose($fh);
        return $data['hits'] <= $max;
    }
}
