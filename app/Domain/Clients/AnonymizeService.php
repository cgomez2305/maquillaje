<?php
declare(strict_types=1);

namespace App\Domain\Clients;

use App\Domain\Audit\AuditLog;
use App\Domain\Audit\EventLog;
use App\Support\Db;

/**
 * Supresión de datos de una clienta (Ley 1581, §14.5): anonimiza nombre, teléfono, correo,
 * cumpleaños, dirección y UTM, pero conserva reservas, pedidos y comisiones para efectos contables.
 * Las direcciones y notas de sus reservas también se borran (son datos personales).
 */
final class AnonymizeService
{
    /** @return bool true si anonimizó; false si no existe o ya estaba anonimizada */
    public static function anonymize(int $clientId, ?int $userId, string $reason, ?string $ip = null): bool
    {
        $reason = trim($reason);
        $done = Db::transaction(static function () use ($clientId, $userId, $reason): ?array {
            $c = Db::one('SELECT id, anonymized_at FROM clients WHERE id = ? FOR UPDATE', [$clientId]);
            if ($c === null || $c['anonymized_at'] !== null) {
                return null;
            }
            $now = now_utc();
            $phoneHash = 'anon:' . hash('sha256', 'client|' . $clientId . '|' . bin2hex(random_bytes(8)));
            Db::run(
                'UPDATE clients SET name = ?, phone_e164 = ?, email = NULL, birthday = NULL, address = NULL, utm = NULL,
                        consent_marketing_at = NULL, anonymized_at = ?, updated_at = ? WHERE id = ?',
                ['Clienta anonimizada #' . $clientId, $phoneHash, $now, $now, $clientId],
            );
            $bookings = Db::all('SELECT id FROM bookings WHERE client_id = ?', [$clientId]);
            Db::run('UPDATE bookings SET address = NULL, notes = NULL, updated_at = ? WHERE client_id = ?', [$now, $clientId]);
            foreach ($bookings as $b) {
                EventLog::record((int) $b['id'], 'clienta_anonimizada', null, null, $reason !== '' ? $reason : 'Supresión de datos', ['clienta' => $clientId], $userId);
            }
            return ['bookings' => count($bookings)];
        });
        if ($done === null) {
            return false;
        }
        AuditLog::record($userId, 'clienta_anonimizada', 'client:' . $clientId, ['motivo' => $reason, 'reservas' => $done['bookings']], $ip);
        return true;
    }

    /**
     * Clientas sin actividad en más de $years años (§14.5), sin reservas futuras activas.
     * @return list<int>
     */
    public static function inactiveSince(int $years = 3): array
    {
        $cut = gmdate('Y-m-d H:i:s', strtotime("-{$years} years"));
        $rows = Db::all(
            "SELECT c.id FROM clients c
             WHERE c.anonymized_at IS NULL AND COALESCE(c.last_activity_at, c.created_at) < ?
               AND NOT EXISTS (SELECT 1 FROM bookings b WHERE b.client_id = c.id AND b.starts_at > ? AND b.status IN ('solicitada','confirmada','reprogramada'))
               AND NOT EXISTS (SELECT 1 FROM bookings b2 WHERE b2.client_id = c.id AND b2.created_at >= ?)",
            [$cut, now_utc(), $cut],
        );
        return array_map(static fn(array $r): int => (int) $r['id'], $rows);
    }
}
