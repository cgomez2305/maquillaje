<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Consecutivos por mes con bloqueo de fila (§9.2): MBF-AAMM-NNNN, WEB-AAMM-NNNN, PED-AAMM-NNNN.
 * Debe llamarse dentro de una transacción.
 */
final class Counter
{
    public static function next(string $name, ?\DateTimeInterface $at = null): int
    {
        $at ??= new \DateTimeImmutable('now', new \DateTimeZone(config('app.timezone', 'America/Bogota')));
        $period = $at->format('ym');
        return Db::transaction(static function () use ($name, $period): int {
            Db::run('INSERT IGNORE INTO counters (name, period, value) VALUES (?, ?, 0)', [$name, $period]);
            $current = (int) Db::value('SELECT value FROM counters WHERE name = ? AND period = ? FOR UPDATE', [$name, $period]);
            $next = $current + 1;
            Db::run('UPDATE counters SET value = ? WHERE name = ? AND period = ?', [$next, $name, $period]);
            return $next;
        });
    }

    /** «MBF-2611-0142» */
    public static function code(string $prefix, ?\DateTimeInterface $at = null): string
    {
        $at ??= new \DateTimeImmutable('now', new \DateTimeZone(config('app.timezone', 'America/Bogota')));
        $n = self::next($prefix, $at);
        return sprintf('%s-%s-%04d', $prefix, $at->format('ym'), $n);
    }
}
