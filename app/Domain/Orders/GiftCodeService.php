<?php
declare(strict_types=1);

namespace App\Domain\Orders;

use App\Domain\Audit\AuditLog;
use App\Domain\Audit\EventLog;
use App\Domain\Booking\Availability;
use App\Domain\Booking\BookingService;
use App\Repositories\ClientRepository;
use App\Support\Db;

/**
 * Bonos de regalo (§11): código único BONO-XXXX-XXXX canjeable en el panel.
 * El canje de un bono de servicio crea una reserva vinculada (gift_code_id) que NO vuelve a
 * comisionar: la comisión ya se generó con el pedido pagado.
 */
final class GiftCodeService
{
    /** Sin 0/O/1/I para que se pueda dictar y escribir sin errores. */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    public const PACKS = ['pack1', 'pack2', 'pack3'];

    public static function generate(int $orderItemId, string $serviceCode, int $value, ?string $recipient): string
    {
        for ($attempt = 0; $attempt < 8; $attempt++) {
            $code = 'BONO-' . self::chunk() . '-' . self::chunk();
            try {
                Db::run(
                    "INSERT INTO gift_codes (code, order_item_id, service_code, value, recipient, status, created_at) VALUES (?, ?, ?, ?, ?, 'activo', ?)",
                    [$code, $orderItemId, $serviceCode, $value, $recipient !== null ? mb_substr($recipient, 0, 120) : null, now_utc()],
                );
                return $code;
            } catch (\PDOException $e) {
                if ((int) ($e->errorInfo[1] ?? 0) !== 1062) {
                    throw $e;
                }
            }
        }
        throw new \RuntimeException('No se pudo generar un código de bono único');
    }

    public static function normalize(string $raw): string
    {
        $c = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $raw) ?? '');
        if (str_starts_with($c, 'BONO')) {
            $c = substr($c, 4);
        }
        return strlen($c) === 8 ? 'BONO-' . substr($c, 0, 4) . '-' . substr($c, 4) : '';
    }

    /** @return array<string,mixed>|null */
    public static function find(string $code): ?array
    {
        return Db::one(
            'SELECT g.*, p.name AS product_name, o.reference AS order_reference, o.client_id AS buyer_id
             FROM gift_codes g JOIN order_items oi ON oi.id = g.order_item_id JOIN products p ON p.id = oi.product_id JOIN orders o ON o.id = oi.order_id
             WHERE g.code = ?',
            [$code],
        );
    }

    /**
     * Canjea un bono. Para bonos de servicio crea la reserva confirmada; para bonos de curso solo
     * marca el canje (la inscripción se coordina con la alumna).
     *
     * @param array{code:string,name:string,phone:string,email?:string,date?:string,time?:string,location?:string,address?:string,notes?:string} $in
     * @return array{error:?string,errors:array<string,string>,booking:?array,gift:?array}
     */
    public static function redeem(array $in, int $userId, ?string $ip = null, ?string $ua = null): array
    {
        $errors = [];
        $code = self::normalize((string) ($in['code'] ?? ''));
        if ($code === '') {
            $errors['code'] = 'Escribe el código con el formato BONO-XXXX-XXXX.';
        }
        $name = trim((string) ($in['name'] ?? ''));
        if (mb_strlen($name) < 3 || mb_strlen($name) > 120) {
            $errors['name'] = 'Escribe el nombre de quien usa el bono.';
        }
        $phone = phone_e164((string) ($in['phone'] ?? ''));
        if ($phone === null) {
            $errors['phone'] = 'Escribe un WhatsApp válido.';
        }
        $email = trim((string) ($in['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Revisa el correo.';
        }
        $gift = $code !== '' ? self::find($code) : null;
        if ($code !== '' && $gift === null) {
            $errors['code'] = 'No existe un bono con ese código.';
        } elseif ($gift !== null && $gift['status'] !== 'activo') {
            $errors['code'] = $gift['status'] === 'canjeado' ? 'Ese bono ya se canjeó.' : 'Ese bono está anulado.';
        }
        $isPack = $gift !== null && in_array($gift['service_code'], self::PACKS, true);
        if ($isPack) {
            $errors += BookingService::validateSchedule([
                'date' => (string) ($in['date'] ?? ''), 'time' => (string) ($in['time'] ?? ''),
                'location' => (string) ($in['location'] ?? ''), 'service' => $gift['service_code'], 'people' => 1,
            ]);
            if (($in['location'] ?? '') === 'home' && mb_strlen(trim((string) ($in['address'] ?? ''))) < 8) {
                $errors['address'] = 'Escribe la dirección del domicilio.';
            }
        }
        if ($errors !== []) {
            return ['error' => 'Revisa los datos del canje.', 'errors' => $errors, 'booking' => null, 'gift' => $gift];
        }

        try {
            $booking = Db::transaction(static function () use ($gift, $isPack, $in, $name, $phone, $email, $userId, $ip, $ua): ?array {
                $g = Db::one('SELECT status FROM gift_codes WHERE id = ? FOR UPDATE', [(int) $gift['id']]);
                if ($g === null || $g['status'] !== 'activo') {
                    throw new \DomainException('Ese bono ya se canjeó.');
                }
                $client = ClientRepository::upsert([
                    'phone_e164' => $phone, 'name' => $name, 'email' => $email !== '' ? mb_strtolower($email) : null,
                    'address' => ($in['location'] ?? '') === 'home' ? trim((string) $in['address']) : null,
                ], 'web_form', true);

                $booking = null;
                if ($isPack) {
                    Db::run("INSERT IGNORE INTO counters (name, period, value) VALUES ('agenda', '0000', 0)");
                    Db::value("SELECT value FROM counters WHERE name = 'agenda' AND period = '0000' FOR UPDATE");
                    if (!Availability::isSlotFree((string) $in['date'], (string) $in['time'], 1)) {
                        throw new \DomainException('Esa hora ya no está disponible.');
                    }
                    $booking = BookingService::insertBooking([
                        'client_id' => $client['id'], 'service' => $gift['service_code'], 'people' => 1,
                        'date' => (string) $in['date'], 'time' => (string) $in['time'], 'location' => (string) $in['location'],
                        'address' => $in['location'] === 'home' ? trim((string) $in['address']) : null,
                        'event_type' => 'otro', 'how_found' => null,
                        'notes' => trim('Canje de ' . $gift['code'] . '. ' . trim((string) ($in['notes'] ?? ''))),
                        'status' => 'confirmada', 'source' => 'web_form', 'is_web_attributed' => 1,
                        'gift_code_id' => (int) $gift['id'], 'deposit_confirmed_at' => now_utc(),
                        'total_override' => (int) $gift['value'], 'created_by' => $userId, 'hold' => false,
                    ], $userId, $ip, $ua, ['bono' => $gift['code']]);
                    EventLog::record((int) $booking['id'], 'bono_canjeado', null, null, null, ['bono' => $gift['code'], 'pedido' => $gift['order_reference'], 'valor' => (int) $gift['value']], $userId, $ip, $ua);
                }
                Db::run(
                    "UPDATE gift_codes SET status = 'canjeado', redeemed_booking_id = ?, redeemed_at = ?, redeemed_by = ? WHERE id = ?",
                    [$booking['id'] ?? null, now_utc(), $userId, (int) $gift['id']],
                );
                return $booking;
            });
        } catch (\DomainException $e) {
            return ['error' => $e->getMessage(), 'errors' => [], 'booking' => null, 'gift' => $gift];
        }

        AuditLog::record($userId, 'bono_canjeado', $gift['code'], ['reserva' => $booking['code'] ?? null, 'servicio' => $gift['service_code']], $ip);
        return ['error' => null, 'errors' => [], 'booking' => $booking, 'gift' => $gift];
    }

    private static function chunk(): string
    {
        $s = '';
        for ($i = 0; $i < 4; $i++) {
            $s .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }
        return $s;
    }
}
