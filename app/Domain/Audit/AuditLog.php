<?php
declare(strict_types=1);

namespace App\Domain\Audit;

use App\Support\Db;

/** Registro de acciones del panel fuera de reservas (login, ajustes, tasas, exportaciones). Solo INSERT. */
final class AuditLog
{
    public static function record(?int $userId, string $action, ?string $target = null, ?array $payload = null, ?string $ip = null): void
    {
        try {
            Db::run(
                'INSERT INTO audit_log (user_id, action, target, payload, ip, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $userId, mb_substr($action, 0, 60), $target !== null ? mb_substr($target, 0, 80) : null,
                    $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
                    $ip !== null && filter_var($ip, FILTER_VALIDATE_IP) ? inet_pton($ip) : null,
                    now_utc(),
                ],
            );
        } catch (\Throwable $e) {
            app_log('error', 'audit_log: ' . $e->getMessage());
        }
    }
}
