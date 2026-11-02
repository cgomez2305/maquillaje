<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Attribution\AttributionService;
use App\Support\Db;

/** Clientas. La identidad es el teléfono normalizado E.164 (§7.1). */
final class ClientRepository
{
    /** @return array<string,mixed>|null */
    public static function findByPhone(string $e164): ?array
    {
        return Db::one('SELECT * FROM clients WHERE phone_e164 = ?', [$e164]);
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Db::one('SELECT * FROM clients WHERE id = ?', [$id]);
    }

    /**
     * Crea o reutiliza la clienta por teléfono. Actualiza datos de contacto con los más recientes;
     * nunca cambia first_source ni first_seen_at. Si es su primer registro web, abre la ventana
     * de atribución de 6 meses.
     *
     * @param array{phone_e164:string,name:string,email?:?string,birthday?:?string,address?:?string,consent_marketing?:bool} $data
     * @return array{id:int,created:bool,client:array<string,mixed>}
     */
    public static function upsert(array $data, string $source, bool $isWeb, ?array $utm = null): array
    {
        $now = now_utc();
        $existing = Db::one('SELECT * FROM clients WHERE phone_e164 = ? FOR UPDATE', [$data['phone_e164']]);
        if ($existing === null) {
            $id = Db::insert(
                'INSERT INTO clients (phone_e164, name, email, birthday, address, first_source, first_seen_at, web_attributed_until, utm,
                                      consent_data_at, consent_marketing_at, last_activity_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $data['phone_e164'], $data['name'], $data['email'] ?? null, $data['birthday'] ?? null, $data['address'] ?? null,
                    $source, $now, $isWeb ? AttributionService::windowEnd($now) : null,
                    $utm ? json_encode($utm, JSON_UNESCAPED_UNICODE) : null,
                    $now, !empty($data['consent_marketing']) ? $now : null, $now, $now, $now,
                ],
            );
            return ['id' => $id, 'created' => true, 'client' => (array) self::find($id)];
        }

        $sets = ['name = ?', 'last_activity_at = ?', 'updated_at = ?', 'consent_data_at = ?'];
        $params = [$data['name'], $now, $now, $now];
        foreach (['email', 'birthday', 'address'] as $f) {
            if (!empty($data[$f])) {
                $sets[] = "{$f} = ?";
                $params[] = $data[$f];
            }
        }
        if (!empty($data['consent_marketing'])) {
            $sets[] = 'consent_marketing_at = COALESCE(consent_marketing_at, ?)';
            $params[] = $now;
        }
        if ($isWeb && $existing['web_attributed_until'] === null) {
            // Primer registro web de una clienta que antes llegó por fuera: abre la ventana desde hoy
            $sets[] = 'web_attributed_until = ?';
            $params[] = AttributionService::windowEnd($now);
            if ($utm && $existing['utm'] === null) {
                $sets[] = 'utm = ?';
                $params[] = json_encode($utm, JSON_UNESCAPED_UNICODE);
            }
        }
        $params[] = (int) $existing['id'];
        Db::run('UPDATE clients SET ' . implode(', ', $sets) . ' WHERE id = ?', $params);
        return ['id' => (int) $existing['id'], 'created' => false, 'client' => (array) self::find((int) $existing['id'])];
    }
}
