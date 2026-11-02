<?php
declare(strict_types=1);

namespace App\Domain\Booking;

use App\Domain\Attribution\AttributionService;
use App\Domain\Audit\EventLog;
use App\Domain\Catalog;
use App\Domain\Commission\RateService;
use App\Domain\Notifications\BookingNotifier;
use App\Repositories\ClientRepository;
use App\Support\Counter;
use App\Support\Db;
use App\Support\Metrics;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Creación de reservas (§7.1, §9). Todo dentro de una transacción con bloqueo de agenda,
 * para que dos personas no tomen la misma hora.
 */
final class BookingService
{
    public const PACKS = ['pack1', 'pack2', 'pack3'];

    /** @return array{unit:int,total:int,deposit:int} */
    public static function quote(string $serviceCode, int $people): array
    {
        $unit = Catalog::price($serviceCode);
        $total = $unit * max(1, $people);
        $deposit = intdiv($total * (int) config('app.booking.deposit_percent', 50), 100);
        return ['unit' => $unit, 'total' => $total, 'deposit' => $deposit];
    }

    /**
     * Valida el paso 1 (servicio y personas).
     * @return array<string,string> errores por campo
     */
    public static function validateService(array $in): array
    {
        $e = [];
        $packs = Catalog::packs();
        if (!in_array($in['service'] ?? '', self::PACKS, true) || !($packs[$in['service']]['active'] ?? true)) {
            $e['service'] = 'Elige uno de los servicios.';
        }
        $p = (int) ($in['people'] ?? 0);
        if ($p < 1 || $p > (int) config('app.booking.max_people', 4)) {
            $e['people'] = 'Elige cuántas personas se maquillan.';
        }
        return $e;
    }

    /**
     * Valida el paso 2 (fecha, hora y lugar).
     * @return array<string,string>
     */
    public static function validateSchedule(array $in): array
    {
        $e = [];
        $date = (string) ($in['date'] ?? '');
        $time = (string) ($in['time'] ?? '');
        $people = (int) ($in['people'] ?? 1);
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', $date, Availability::tz());
        if ($d === false || $d->format('Y-m-d') !== $date) {
            $e['date'] = 'Elige un día en el calendario.';
        } else {
            $reason = Availability::dayClosedReason($d);
            if ($reason === 'anticipacion') {
                $e['date'] = 'Agenda con mínimo ' . (int) config('app.booking.min_days_ahead', 2) . ' días de anticipación.';
            } elseif ($reason !== null) {
                $e['date'] = 'Ese día no está disponible. Elige otro.';
            }
        }
        if (!in_array($time, \App\Support\Settings::slots(), true)) {
            $e['time'] = 'Elige la hora de inicio.';
        } elseif (!isset($e['date']) && !Availability::isSlotFree($date, $time, $people)) {
            $e['time'] = 'Esa hora ya no está disponible ese día. Elige otra.';
        }
        $loc = (string) ($in['location'] ?? '');
        if (!in_array($loc, ['studio', 'home'], true)) {
            $e['location'] = 'Elige dónde será el maquillaje.';
        } elseif ($loc === 'home' && ($in['service'] ?? '') === 'pack1') {
            // §7.1: el domicilio fuerza Pack 2 o Pack 3
            $e['location'] = 'El servicio a domicilio se reserva con el Pack 2 o el Pack 3. Cambia el servicio o elige el estudio.';
        }
        return $e;
    }

    /**
     * Valida y limpia el paso 3 (datos). Devuelve [errores, datos limpios].
     * @return array{0:array<string,string>,1:array<string,mixed>}
     */
    public static function validateDetails(array $in, string $location): array
    {
        $e = [];
        $clean = [];
        $name = trim(preg_replace('/\s+/u', ' ', (string) ($in['name'] ?? '')) ?? '');
        if (mb_strlen($name) < 3 || mb_strlen($name) > 120 || preg_match('/[<>{}\\\\]/', $name)) {
            $e['name'] = 'Escribe tu nombre completo.';
        }
        $clean['name'] = mb_substr($name, 0, 120);

        $phone = phone_e164((string) ($in['phone'] ?? ''));
        if ($phone === null) {
            $e['phone'] = 'Escribe un número de WhatsApp válido, por ejemplo 300 123 4567.';
        }
        $clean['phone_e164'] = $phone;

        $email = trim((string) ($in['email'] ?? ''));
        if (mb_strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'Escribe un correo válido: ahí te llega el código y los datos de pago.';
        }
        $clean['email'] = mb_strtolower($email);

        $birthday = trim((string) ($in['birthday'] ?? ''));
        $clean['birthday'] = null;
        if ($birthday !== '') {
            $b = DateTimeImmutable::createFromFormat('!Y-m-d', $birthday);
            $year = $b ? (int) $b->format('Y') : 0;
            if ($b === false || $b->format('Y-m-d') !== $birthday || $year < 1920 || $b > new DateTimeImmutable('today')) {
                $e['birthday'] = 'Revisa la fecha de cumpleaños.';
            } else {
                $clean['birthday'] = $birthday;
            }
        }

        $address = trim((string) ($in['address'] ?? ''));
        if ($location === 'home' && mb_strlen($address) < 8) {
            $e['address'] = 'Escribe la dirección del servicio a domicilio (barrio y ciudad).';
        }
        $clean['address'] = $address !== '' ? mb_substr($address, 0, 255) : null;

        $events = (array) config('app.booking.event_types', []);
        $event = (string) ($in['event_type'] ?? '');
        if (!array_key_exists($event, $events)) {
            $e['event_type'] = 'Elige el tipo de evento.';
        }
        $clean['event_type'] = $event;

        $how = (string) ($in['how_found'] ?? '');
        $clean['how_found'] = array_key_exists($how, (array) config('app.booking.how_found', [])) ? $how : null;

        $notes = trim((string) ($in['notes'] ?? ''));
        if (mb_strlen($notes) > 1000) {
            $e['notes'] = 'La nota puede tener hasta 1000 caracteres.';
        }
        $clean['notes'] = $notes !== '' ? mb_substr($notes, 0, 1000) : null;

        if (empty($in['consent'])) {
            $e['consent'] = 'Necesitamos tu autorización para gestionar la reserva.';
        }
        $clean['consent_marketing'] = !empty($in['marketing']);
        return [$e, $clean];
    }

    /**
     * Crea la reserva enviada por la clienta desde /reservar.
     *
     * @param array{service:string,people:int,date:string,time:string,location:string} $sel
     * @param array<string,mixed> $details  salida de validateDetails
     * @return array<string,mixed> reserva creada
     * @throws SlotTakenException si la hora se ocupó mientras llenaba el formulario
     */
    public static function createFromWeb(array $sel, array $details, string $ip, string $ua): array
    {
        $utm = AttributionService::current();
        $attr = AttributionService::forWebBooking($utm);
        $firstSource = $attr['source'] === 'google_business' ? 'google_business' : 'web_form';

        $booking = Db::transaction(static function () use ($sel, $details, $ip, $ua, $utm, $attr, $firstSource): array {
            // Bloqueo de agenda: serializa la verificación de disponibilidad + inserción
            Db::run("INSERT IGNORE INTO counters (name, period, value) VALUES ('agenda', '0000', 0)");
            Db::value("SELECT value FROM counters WHERE name = 'agenda' AND period = '0000' FOR UPDATE");
            if (!Availability::isSlotFree($sel['date'], $sel['time'], (int) $sel['people'])) {
                throw new SlotTakenException('La hora ya no está disponible.');
            }

            $client = ClientRepository::upsert([
                'phone_e164' => $details['phone_e164'],
                'name' => $details['name'],
                'email' => $details['email'],
                'birthday' => $details['birthday'],
                'address' => $details['address'],
                'consent_marketing' => $details['consent_marketing'],
            ], $firstSource, true, $utm);

            return self::insertBooking([
                'client_id' => $client['id'],
                'service' => $sel['service'],
                'people' => (int) $sel['people'],
                'date' => $sel['date'],
                'time' => $sel['time'],
                'location' => $sel['location'],
                'address' => $sel['location'] === 'home' ? $details['address'] : null,
                'event_type' => $details['event_type'],
                'how_found' => $details['how_found'],
                'notes' => $details['notes'],
                'status' => 'solicitada',
                'source' => $attr['source'],
                'is_web_attributed' => $attr['is_web_attributed'],
                'lead_id' => null,
                'created_by' => null,
                'hold' => true,
            ], null, $ip, $ua, ['utm' => $utm, 'client_created' => $client['created']]);
        });

        Metrics::bump('booking_submits');
        BookingNotifier::created($booking);
        return $booking;
    }

    /**
     * Inserta la reserva + evento «creada». Debe llamarse dentro de una transacción.
     *
     * @param array<string,mixed> $b
     * @return array<string,mixed>
     */
    public static function insertBooking(array $b, ?int $actorUserId, ?string $ip, ?string $ua, array $eventPayload = []): array
    {
        $tz = Availability::tz();
        $startLocal = new DateTimeImmutable($b['date'] . ' ' . $b['time'], $tz);
        $startUtc = $startLocal->setTimezone(new DateTimeZone('UTC'));
        $endUtc = $startUtc->modify('+' . Availability::durationMinutes((int) $b['people']) . ' minutes');
        $q = self::quote($b['service'], (int) $b['people']);
        if (isset($b['total_override'])) {
            $q['total'] = (int) $b['total_override'];
            $q['deposit'] = intdiv($q['total'] * (int) config('app.booking.deposit_percent', 50), 100);
        }
        $serviceId = (int) Db::value('SELECT id FROM services WHERE code = ?', [$b['service']]);
        $rate = ($b['is_web_attributed'] && empty($b['gift_code_id'])) ? RateService::current('service') : 0;
        $now = now_utc();
        $code = Counter::code('MBF');
        $token = random_token(32);
        // Retención de 48 h sin abono, pero nunca más allá de 12 h antes de la cita (mínimo 2 h)
        $hold = null;
        if (!empty($b['hold'])) {
            $holdTs = min(time() + 3600 * (int) config('app.booking.hold_hours', 48), $startUtc->getTimestamp() - 12 * 3600);
            $hold = gmdate('Y-m-d H:i:s', max($holdTs, time() + 2 * 3600));
        }

        $id = Db::insert(
            'INSERT INTO bookings (code, public_token, client_id, service_id, people, starts_at, ends_at, location, address, event_type, how_found, notes,
                                   unit_price, total, deposit_amount, deposit_confirmed_at, hold_expires_at, status, source, is_web_attributed,
                                   commission_rate_bp, lead_id, gift_code_id, created_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $code, $token, (int) $b['client_id'], $serviceId, (int) $b['people'],
                $startUtc->format('Y-m-d H:i:s'), $endUtc->format('Y-m-d H:i:s'),
                $b['location'], $b['address'], $b['event_type'], $b['how_found'] ?? null, $b['notes'],
                $q['unit'], $q['total'], $q['deposit'], $b['deposit_confirmed_at'] ?? null, $hold,
                $b['status'], $b['source'], (int) $b['is_web_attributed'], $rate,
                $b['lead_id'] ?? null, $b['gift_code_id'] ?? null, $b['created_by'] ?? null, $now, $now,
            ],
        );

        EventLog::record($id, 'creada', null, $b['status'], null, [
            'servicio' => $b['service'], 'personas' => (int) $b['people'], 'inicio' => $startUtc->format('Y-m-d H:i:s'),
            'lugar' => $b['location'], 'total' => $q['total'], 'abono' => $q['deposit'], 'origen' => $b['source'],
            'atribuida_web' => (int) $b['is_web_attributed'], 'tasa_bp' => $rate,
        ] + $eventPayload, $actorUserId, $ip, $ua);

        return (array) self::find($id);
    }

    /** @return array<string,mixed>|null reserva con datos de clienta y servicio */
    public static function find(int $id): ?array
    {
        return Db::one(self::SELECT . ' WHERE b.id = ?', [$id]);
    }

    /** @return array<string,mixed>|null */
    public static function findByCode(string $code): ?array
    {
        return Db::one(self::SELECT . ' WHERE b.code = ?', [$code]);
    }

    public const SELECT = 'SELECT b.*, s.code AS service_code, s.name AS service_name, c.name AS client_name, c.phone_e164, c.email AS client_email,
                                  c.birthday AS client_birthday, c.web_attributed_until
                           FROM bookings b JOIN services s ON s.id = b.service_id JOIN clients c ON c.id = b.client_id';
}
