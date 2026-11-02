<?php
declare(strict_types=1);

namespace App\Support;

/** Ajustes editables desde el panel (tabla settings, valores JSON), con respaldo en config. */
final class Settings
{
    /** @var array<string,mixed> */
    private static array $cache = [];

    public static function get(string $name, mixed $default = null): mixed
    {
        if (array_key_exists($name, self::$cache)) {
            return self::$cache[$name];
        }
        try {
            $raw = Db::value('SELECT value FROM settings WHERE name = ?', [$name]);
            $value = $raw === null ? $default : json_decode((string) $raw, true);
        } catch (\Throwable) {
            $value = $default;
        }
        return self::$cache[$name] = $value ?? $default;
    }

    public static function set(string $name, mixed $value, ?int $userId = null): void
    {
        Db::run(
            'INSERT INTO settings (name, value, updated_by, updated_at) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE value = VALUES(value), updated_by = VALUES(updated_by), updated_at = VALUES(updated_at)',
            [$name, json_encode($value, JSON_UNESCAPED_UNICODE), $userId, now_utc()],
        );
        self::$cache[$name] = $value;
    }

    public static function flush(): void
    {
        self::$cache = [];
    }

    /** @return list<string> horas «HH:MM» ofrecidas */
    public static function slots(): array
    {
        $slots = self::get('booking_slots', config('app.booking.slots'));
        $slots = array_values(array_filter((array) $slots, static fn($s): bool => is_string($s) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $s) === 1));
        sort($slots);
        return $slots;
    }
}
