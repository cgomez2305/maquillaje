<?php
declare(strict_types=1);

namespace App\Domain\Audit;

use App\Support\Db;

/**
 * Historial inmutable de reservas con cadena de hashes SHA-256 (§9.6).
 * Cada evento guarda el hash del anterior (cadena global). La inserción se serializa con
 * un bloqueo de fila en counters('event_chain') para que dos eventos simultáneos no compartan prev_hash.
 */
final class EventLog
{
    /**
     * @param array<string,mixed>|null $payload
     */
    public static function record(
        int $bookingId,
        string $type,
        ?string $from = null,
        ?string $to = null,
        ?string $reason = null,
        ?array $payload = null,
        ?int $actorUserId = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): int {
        return Db::transaction(static function () use ($bookingId, $type, $from, $to, $reason, $payload, $actorUserId, $ip, $userAgent): int {
            Db::value("SELECT value FROM counters WHERE name = 'event_chain' AND period = '0000' FOR UPDATE");
            $prev = Db::value('SELECT hash FROM booking_events ORDER BY id DESC LIMIT 1');
            $prev = $prev === null ? null : (string) $prev;
            $createdAt = now_utc();
            $payloadJson = $payload === null ? null : json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $row = [
                'booking_id' => $bookingId, 'type' => $type, 'from_status' => $from, 'to_status' => $to,
                'reason' => $reason, 'payload' => $payloadJson, 'actor_user_id' => $actorUserId, 'created_at' => $createdAt,
            ];
            $hash = self::hash($row, $prev);
            $id = Db::insert(
                'INSERT INTO booking_events (booking_id, type, from_status, to_status, reason, payload, actor_user_id, ip, user_agent, prev_hash, hash, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $bookingId, $type, $from, $to, $reason !== null ? mb_substr($reason, 0, 500) : null, $payloadJson, $actorUserId,
                    $ip !== null && filter_var($ip, FILTER_VALIDATE_IP) ? inet_pton($ip) : null,
                    $userAgent !== null ? mb_substr($userAgent, 0, 255) : null,
                    $prev, $hash, $createdAt,
                ],
            );
            Db::run("UPDATE counters SET value = value + 1 WHERE name = 'event_chain' AND period = '0000'");
            return $id;
        });
    }

    /** @param array<string,mixed> $row */
    public static function hash(array $row, ?string $prev): string
    {
        $payload = $row['payload'];
        if (is_string($payload) && $payload !== '') {
            // JSON canónico: MySQL 8 reordena claves y espacios al guardar una columna JSON
            $payload = self::canonicalJson(json_decode($payload, true));
        }
        $canonical = implode("\x1F", [
            (string) $row['booking_id'], (string) $row['type'], (string) ($row['from_status'] ?? ''), (string) ($row['to_status'] ?? ''),
            (string) ($row['reason'] ?? ''), (string) ($payload ?? ''), (string) ($row['actor_user_id'] ?? ''), (string) $row['created_at'],
        ]);
        return hash('sha256', ($prev ?? 'GENESIS') . "\x1E" . $canonical);
    }

    private static function canonicalJson(mixed $value): string
    {
        $sort = static function (mixed $v) use (&$sort): mixed {
            if (is_array($v)) {
                if (!array_is_list($v)) {
                    ksort($v, SORT_STRING);
                }
                return array_map($sort, $v);
            }
            return $v;
        };
        return (string) json_encode($sort($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * Recalcula toda la cadena. Devuelve el resultado y el primer evento roto, si existe.
     *
     * @return array{ok:bool,count:int,broken_id:?int,reason:?string}
     */
    public static function verify(): array
    {
        $prev = null;
        $count = 0;
        $st = Db::run('SELECT id, booking_id, type, from_status, to_status, reason, payload, actor_user_id, prev_hash, hash, created_at FROM booking_events ORDER BY id ASC');
        while ($row = $st->fetch()) {
            $count++;
            if (($row['prev_hash'] ?? null) !== $prev) {
                return ['ok' => false, 'count' => $count, 'broken_id' => (int) $row['id'], 'reason' => 'El enlace con el evento anterior no coincide (falta o se alteró un evento).'];
            }
            if (!hash_equals(self::hash($row, $prev), (string) $row['hash'])) {
                return ['ok' => false, 'count' => $count, 'broken_id' => (int) $row['id'], 'reason' => 'El contenido del evento no coincide con su hash (se modificó).'];
            }
            $prev = (string) $row['hash'];
        }
        $expected = (int) Db::value("SELECT value FROM counters WHERE name = 'event_chain' AND period = '0000'");
        if ($expected !== $count) {
            return ['ok' => false, 'count' => $count, 'broken_id' => null, 'reason' => "Hay {$count} eventos pero el contador registra {$expected}: se eliminaron eventos al final de la cadena."];
        }
        return ['ok' => true, 'count' => $count, 'broken_id' => null, 'reason' => null];
    }

    /** @return list<array<string,mixed>> */
    public static function forBooking(int $bookingId): array
    {
        return Db::all(
            'SELECT e.*, u.name AS actor_name FROM booking_events e LEFT JOIN users u ON u.id = e.actor_user_id WHERE e.booking_id = ? ORDER BY e.id ASC',
            [$bookingId],
        );
    }
}
