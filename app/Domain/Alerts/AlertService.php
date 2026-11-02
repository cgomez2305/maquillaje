<?php
declare(strict_types=1);

namespace App\Domain\Alerts;

use App\Domain\Notifications\Mailer;
use App\Support\Db;

/**
 * Alertas al socio (§9.6): correo inmediato + resumen semanal (cron/alertas.php --resumen).
 * Idempotentes por dedupe_key: la misma situación no alerta dos veces.
 */
final class AlertService
{
    public const TYPES = [
        'cancelacion_web' => 'Cancelación o no asistencia de una reserva atribuida a la web',
        'sin_marcar' => 'Reserva confirmada sin marcar como realizada 72 h después',
        'cambio_valor' => 'Cambio de valor o de servicio después de confirmada',
        'encuesta_contradictoria' => 'Encuesta contradictoria con el estado',
        'telefono_atribuido' => 'Clienta con teléfono atribuido registrada como origen manual',
    ];

    public static function raise(string $type, string $message, string $dedupeKey, ?int $bookingId = null, ?int $clientId = null, array $payload = []): bool
    {
        if (!array_key_exists($type, self::TYPES)) {
            throw new \InvalidArgumentException('Tipo de alerta desconocido');
        }
        try {
            Db::run(
                'INSERT INTO alerts (type, booking_id, client_id, message, payload, dedupe_key, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$type, $bookingId, $clientId, mb_substr($message, 0, 500), $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null, mb_substr($dedupeKey, 0, 120), now_utc()],
            );
        } catch (\PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return false; // ya existía
            }
            throw $e;
        }
        $id = (int) Db::pdo()->lastInsertId();
        $partner = Mailer::partner();
        if ($partner !== '') {
            $ok = Mailer::send($partner, 'Alerta · ' . self::TYPES[$type], 'alerta', [
                'type' => $type, 'typeLabel' => self::TYPES[$type], 'message' => $message,
                'url' => url('/panel' . ($bookingId ? '/reservas/' . (string) Db::value('SELECT code FROM bookings WHERE id = ?', [$bookingId]) : '')),
            ], 'alerta-' . $id);
            if ($ok) {
                Db::run('UPDATE alerts SET notified_at = ? WHERE id = ?', [now_utc(), $id]);
            }
        }
        return true;
    }

    /** @return list<array<string,mixed>> */
    public static function recent(int $limit = 10, int $days = 30): array
    {
        return Db::all(
            'SELECT a.*, b.code FROM alerts a LEFT JOIN bookings b ON b.id = a.booking_id WHERE a.created_at >= ? ORDER BY a.created_at DESC LIMIT ' . max(1, min(100, $limit)),
            [gmdate('Y-m-d H:i:s', time() - 86400 * $days)],
        );
    }
}
