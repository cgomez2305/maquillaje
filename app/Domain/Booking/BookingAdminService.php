<?php
declare(strict_types=1);

namespace App\Domain\Booking;

use App\Domain\Alerts\AlertService;
use App\Domain\Attribution\AttributionService;
use App\Domain\Attribution\Source;
use App\Domain\Audit\AuditLog;
use App\Domain\Audit\EventLog;
use App\Domain\Catalog;
use App\Domain\Commission\CommissionService;
use App\Domain\Notifications\BookingNotifier;
use App\Repositories\ClientRepository;
use App\Support\Db;
use App\Support\Settings;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Operaciones del panel sobre reservas (§9.1, §9.3, §9.6, §14.3): listado, transiciones de estado,
 * edición, alta manual con atribución automática, comprobantes y calendario.
 * Los controladores solo traducen HTTP; toda regla vive aquí.
 *
 * $actor = ['id' => int, 'role' => 'owner'|'partner'|'staff', 'ip' => string, 'ua' => string]
 */
final class BookingAdminService
{
    public const FILTERS = [
        '' => ['Todas', null],
        'solicitadas' => ['Solicitadas', ['solicitada']],
        'confirmadas' => ['Confirmadas', ['confirmada', 'reprogramada']],
        'realizadas' => ['Realizadas', ['realizada']],
        'canceladas' => ['Canceladas', ['cancelada', 'no_asistio', 'vencida']],
    ];

    private const SORTS = [
        'fecha' => 'b.starts_at DESC, b.id DESC',
        'fecha_asc' => 'b.starts_at ASC, b.id ASC',
        'creada' => 'b.created_at DESC, b.id DESC',
    ];

    public const PER_PAGE = 25;

    /** Transiciones que solo puede hacer la dueña (§9.7). */
    private const OWNER_ONLY = ['cancelada', 'no_asistio'];

    /** Estados en que todavía se puede cambiar servicio, personas o valor. */
    private const EDITABLE = ['solicitada', 'confirmada', 'reprogramada'];

    public const FIRST_SOURCES = [
        'instagram' => 'Instagram',
        'tiktok' => 'TikTok',
        'referral' => 'Recomendación',
        'manual_other' => 'Otro (llamada, visita, etc.)',
    ];

    public const MAX_UPLOAD_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    /* ------------------------------------------------------------------ listado */

    /**
     * @return array{rows:list<array<string,mixed>>,total:int,page:int,pages:int}
     */
    public static function list(string $filter, string $q, string $sort = 'fecha', int $page = 1): array
    {
        $where = [];
        $params = [];
        $statuses = self::FILTERS[$filter][1] ?? null;
        if ($statuses !== null) {
            $where[] = 'b.status IN (' . implode(',', array_fill(0, count($statuses), '?')) . ')';
            array_push($params, ...$statuses);
        }
        $q = trim($q);
        if ($q !== '') {
            $like = '%' . addcslashes(mb_substr($q, 0, 80), '%_\\') . '%';
            $or = ['b.code LIKE ?', 'c.name LIKE ?', 'c.phone_e164 LIKE ?'];
            array_push($params, $like, $like, $like);
            $phone = phone_e164($q);
            if ($phone !== null) {
                $or[] = 'c.phone_e164 = ?';
                $params[] = $phone;
            } else {
                $digits = preg_replace('/\D/', '', $q) ?? '';
                if (strlen($digits) >= 4) {
                    $or[] = 'c.phone_e164 LIKE ?';
                    $params[] = '%' . $digits . '%';
                }
            }
            $where[] = '(' . implode(' OR ', $or) . ')';
        }
        $whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $order = self::SORTS[$sort] ?? self::SORTS['fecha'];
        $total = (int) Db::value('SELECT COUNT(*) FROM bookings b JOIN clients c ON c.id = b.client_id' . $whereSql, $params);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = max(1, min($pages, $page));
        $offset = ($page - 1) * self::PER_PAGE;
        $rows = Db::all(BookingService::SELECT . $whereSql . ' ORDER BY ' . $order . ' LIMIT ' . self::PER_PAGE . ' OFFSET ' . $offset, $params);
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    /** @return list<array<string,mixed>> */
    public static function files(int $bookingId): array
    {
        return Db::all(
            'SELECT f.id, f.kind, f.mime, f.size_bytes, f.original_name, f.created_at, u.name AS uploader
             FROM booking_files f LEFT JOIN users u ON u.id = f.uploaded_by WHERE f.booking_id = ? ORDER BY f.id',
            [$bookingId],
        );
    }

    /**
     * Acciones que el rol puede ejecutar sobre la reserva.
     * @return list<string> estados destino
     */
    public static function allowedTransitions(array $b, string $role): array
    {
        if (!in_array($role, ['owner', 'staff'], true)) {
            return [];
        }
        $out = [];
        foreach (BookingStateMachine::next($b['status']) as $to) {
            if ($to === 'vencida') {
                continue; // solo el cron
            }
            if (in_array($to, self::OWNER_ONLY, true) && $role !== 'owner') {
                continue;
            }
            $out[] = $to;
        }
        return $out;
    }

    public static function canEdit(array $b, string $role): bool
    {
        return in_array($role, ['owner', 'staff'], true);
    }

    public static function canEditValue(array $b): bool
    {
        return in_array($b['status'], self::EDITABLE, true);
    }

    /* ------------------------------------------------------------- transiciones */

    /**
     * Cambia el estado. Para reprogramar se pasa $date (Y-m-d) y $time (HH:MM).
     * @return string|null error
     */
    public static function transition(string $code, string $to, ?string $reason, ?string $date, ?string $time, array $actor, ?DateTimeImmutable $nowLocal = null): ?string
    {
        if (!in_array($actor['role'] ?? '', ['owner', 'staff'], true)) {
            return 'Tu rol no puede modificar reservas.';
        }
        if ($to === 'vencida') {
            return 'Las reservas vencen solas cuando pasan 48 h sin abono.';
        }
        if (in_array($to, self::OWNER_ONLY, true) && $actor['role'] !== 'owner') {
            return 'Solo la dueña puede cancelar o marcar «No asistió».';
        }
        $reason = $reason !== null ? trim(mb_substr($reason, 0, 500)) : null;
        $reason = $reason === '' ? null : $reason;

        $after = [];
        try {
            $result = Db::transaction(static function () use ($code, $to, $reason, $date, $time, $actor, $nowLocal, &$after): ?string {
                Db::run("INSERT IGNORE INTO counters (name, period, value) VALUES ('agenda', '0000', 0)");
                Db::value("SELECT value FROM counters WHERE name = 'agenda' AND period = '0000' FOR UPDATE");
                $b = Db::one('SELECT * FROM bookings WHERE code = ? FOR UPDATE', [$code]);
                if ($b === null) {
                    return 'La reserva no existe.';
                }
                $error = BookingStateMachine::check($b, $to, $reason, $nowLocal);
                if ($error !== null) {
                    return $error;
                }
                $id = (int) $b['id'];
                $now = now_utc();
                $from = $b['status'];
                $ev = static fn(string $type, ?string $f, ?string $t, ?string $why, ?array $payload) => EventLog::record($id, $type, $f, $t, $why, $payload, (int) $actor['id'], $actor['ip'] ?? null, $actor['ua'] ?? null);

                switch ($to) {
                    case 'confirmada':
                        // solicitada → confirmada: abono recibido
                        Db::run('UPDATE bookings SET status = ?, deposit_confirmed_at = COALESCE(deposit_confirmed_at, ?), hold_expires_at = NULL, updated_at = ? WHERE id = ?', ['confirmada', $now, $now, $id]);
                        $ev('abono_confirmado', $from, 'confirmada', $reason, ['abono' => (int) $b['deposit_amount'], 'antes' => ['estado' => $from], 'despues' => ['estado' => 'confirmada']]);
                        $after = ['notify' => 'deposit', 'id' => $id];
                        break;

                    case 'reprogramada':
                        $err = self::validateNewSlot($date, $time);
                        if ($err !== null) {
                            return $err;
                        }
                        if (!Availability::isSlotFree((string) $date, (string) $time, (int) $b['people'], $id)) {
                            return 'Esa fecha y hora no están disponibles. Elige otra.';
                        }
                        [$startUtc, $endUtc] = self::interval((string) $date, (string) $time, (int) $b['people']);
                        $before = ['inicio' => $b['starts_at'], 'fin' => $b['ends_at']];
                        Db::run('UPDATE bookings SET status = ?, updated_at = ? WHERE id = ?', ['reprogramada', $now, $id]);
                        $ev('reprogramada', $from, 'reprogramada', $reason, ['antes' => $before, 'despues' => ['inicio' => $startUtc, 'fin' => $endUtc]]);
                        Db::run('UPDATE bookings SET status = ?, starts_at = ?, ends_at = ?, reminder_sent_at = NULL, updated_at = ? WHERE id = ?', ['confirmada', $startUtc, $endUtc, $now, $id]);
                        $ev('confirmada', 'reprogramada', 'confirmada', null, ['nueva_fecha' => $startUtc]);
                        $after = ['notify' => 'rescheduled', 'id' => $id];
                        break;

                    default:
                        // realizada, no_asistio, cancelada (y confirmada desde reprogramada)
                        Db::run('UPDATE bookings SET status = ?, hold_expires_at = NULL, updated_at = ? WHERE id = ?', [$to, $now, $id]);
                        $ev($to, $from, $to, $reason, ['antes' => ['estado' => $from], 'despues' => ['estado' => $to]]);
                        $after = ['id' => $id, 'to' => $to, 'web' => (int) $b['is_web_attributed'], 'code' => $b['code'], 'client_id' => (int) $b['client_id'], 'reason' => $reason];
                }
                return null;
            });
        } catch (\PDOException $e) {
            app_log('error', 'transición de reserva: ' . $e->getMessage());
            return 'No se pudo guardar el cambio.';
        }
        if ($result !== null) {
            return $result;
        }

        // Efectos posteriores (fuera de la transacción)
        $id = (int) ($after['id'] ?? 0);
        if (($after['notify'] ?? '') === 'deposit') {
            BookingNotifier::depositConfirmed((array) BookingService::find($id));
        } elseif (($after['notify'] ?? '') === 'rescheduled') {
            BookingNotifier::rescheduled((array) BookingService::find($id));
        }
        $toState = $after['to'] ?? null;
        if (in_array($toState, ['realizada', 'no_asistio'], true)) {
            CommissionService::recordBooking($id);
        }
        if (in_array($toState, ['cancelada', 'no_asistio'], true) && ($after['web'] ?? 0) === 1) {
            $label = $toState === 'cancelada' ? 'se canceló' : 'se marcó como «No asistió»';
            AlertService::raise(
                'cancelacion_web',
                $after['code'] . ' ' . $label . '. Motivo: «' . ($after['reason'] ?? '') . '».',
                ($toState === 'cancelada' ? 'cancel-' : 'noshow-') . $after['code'],
                $id,
                $after['client_id'],
                ['motivo' => $after['reason'], 'estado' => $toState],
            );
        }
        return null;
    }

    /* ------------------------------------------------------------------ edición */

    /**
     * @param array<string,mixed> $in service, people, total, notes, address, reason
     * @return string|null error
     */
    public static function update(string $code, array $in, array $actor): ?string
    {
        if (!in_array($actor['role'] ?? '', ['owner', 'staff'], true)) {
            return 'Tu rol no puede modificar reservas.';
        }
        $after = null;
        try {
            $result = Db::transaction(static function () use ($code, $in, $actor, &$after): ?string {
                Db::run("INSERT IGNORE INTO counters (name, period, value) VALUES ('agenda', '0000', 0)");
                Db::value("SELECT value FROM counters WHERE name = 'agenda' AND period = '0000' FOR UPDATE");
                $b = Db::one('SELECT b.*, s.code AS service_code FROM bookings b JOIN services s ON s.id = b.service_id WHERE b.code = ? FOR UPDATE', [$code]);
                if ($b === null) {
                    return 'La reserva no existe.';
                }
                $id = (int) $b['id'];
                $reason = trim(mb_substr((string) ($in['reason'] ?? ''), 0, 500));
                $notes = trim(mb_substr((string) ($in['notes'] ?? (string) $b['notes']), 0, 1000));
                $address = trim(mb_substr((string) ($in['address'] ?? (string) $b['address']), 0, 255));

                $service = (string) ($in['service'] ?? $b['service_code']);
                $people = (int) ($in['people'] ?? $b['people']);
                $total = isset($in['total']) && $in['total'] !== '' ? (int) preg_replace('/\D/', '', (string) $in['total']) : (int) $b['total'];

                $valueChanged = $service !== $b['service_code'] || $people !== (int) $b['people'] || $total !== (int) $b['total'];
                if ($valueChanged && !self::canEditValue($b)) {
                    return 'En este estado ya no se puede cambiar el servicio, las personas ni el valor.';
                }
                if (!in_array($service, BookingService::PACKS, true)) {
                    return 'Servicio no válido.';
                }
                if ($people < 1 || $people > (int) config('app.booking.max_people', 4)) {
                    return 'Número de personas no válido.';
                }
                if ($total < 0 || $total > 50000000) {
                    return 'Valor no válido.';
                }
                if ($b['location'] === 'home' && mb_strlen($address) < 8) {
                    return 'La dirección del domicilio es obligatoria.';
                }
                // Si cambió servicio o personas y el total no se tocó a mano, recalcula
                if (($service !== $b['service_code'] || $people !== (int) $b['people']) && $total === (int) $b['total']) {
                    $total = BookingService::quote($service, $people)['total'];
                }
                $sensitive = $total !== (int) $b['total'] || $service !== $b['service_code'];
                $afterConfirm = $b['status'] !== 'solicitada';
                if ($sensitive && $afterConfirm && mb_strlen($reason) < 3) {
                    return 'Escribe el motivo: es obligatorio para cambiar valor o servicio de una reserva confirmada.';
                }
                if ($people !== (int) $b['people']) {
                    $start = local_time($b['starts_at']);
                    if ($start && !self::slotFreeAt($start, $people, $id)) {
                        return 'Con más personas la cita se cruza con otra. Ajusta la hora primero.';
                    }
                }

                $serviceId = (int) Db::value('SELECT id FROM services WHERE code = ?', [$service]);
                $unit = Catalog::price($service);
                $deposit = $b['deposit_confirmed_at'] !== null ? (int) $b['deposit_amount'] : intdiv($total * (int) config('app.booking.deposit_percent', 50), 100);
                $ends = (new DateTimeImmutable($b['starts_at'], new DateTimeZone('UTC')))->modify('+' . Availability::durationMinutes($people) . ' minutes')->format('Y-m-d H:i:s');

                $before = ['servicio' => $b['service_code'], 'personas' => (int) $b['people'], 'total' => (int) $b['total'], 'abono' => (int) $b['deposit_amount'], 'notas' => $b['notes'], 'direccion' => $b['address']];
                $afterVals = ['servicio' => $service, 'personas' => $people, 'total' => $total, 'abono' => $deposit, 'notas' => $notes === '' ? null : $notes, 'direccion' => $address === '' ? null : $address];
                if ($before === $afterVals) {
                    return 'No hay cambios para guardar.';
                }
                Db::run(
                    'UPDATE bookings SET service_id = ?, people = ?, unit_price = ?, total = ?, deposit_amount = ?, ends_at = ?, notes = ?, address = ?, updated_at = ? WHERE id = ?',
                    [$serviceId, $people, $unit, $total, $deposit, $ends, $afterVals['notas'], $afterVals['direccion'], now_utc(), $id],
                );
                $changed = array_keys(array_filter($afterVals, static fn($v, $k) => $before[$k] !== $v, ARRAY_FILTER_USE_BOTH));
                $type = $sensitive ? 'valor_modificado' : 'datos_modificados';
                EventLog::record($id, $type, null, null, $reason !== '' ? $reason : null, [
                    'antes' => array_intersect_key($before, array_flip($changed)),
                    'despues' => array_intersect_key($afterVals, array_flip($changed)),
                ], (int) $actor['id'], $actor['ip'] ?? null, $actor['ua'] ?? null);
                if ($sensitive && $afterConfirm) {
                    $after = ['id' => $id, 'code' => $b['code'], 'client_id' => (int) $b['client_id'], 'before' => $before, 'after' => $afterVals, 'reason' => $reason];
                }
                return null;
            });
        } catch (\PDOException $e) {
            app_log('error', 'edición de reserva: ' . $e->getMessage());
            return 'No se pudo guardar el cambio.';
        }
        if ($result === null && $after !== null) {
            AlertService::raise(
                'cambio_valor',
                sprintf('%s cambió de %s (%s) a %s (%s) después de confirmada. Motivo: «%s».', $after['code'], $after['before']['servicio'], money($after['before']['total']), $after['after']['servicio'], money($after['after']['total']), $after['reason']),
                'valor-' . $after['code'] . '-' . substr(hash('sha256', json_encode($after['after'])), 0, 12),
                $after['id'],
                $after['client_id'],
                ['antes' => $after['before'], 'despues' => $after['after']],
            );
        }
        return $result;
    }

    /* --------------------------------------------------------------- alta manual */

    /**
     * Registra una reserva que llegó por fuera del formulario (WhatsApp, llamada, Instagram…).
     * La atribución la decide el sistema y no se puede desmarcar.
     *
     * @return array{errors:array<string,string>,booking:?array<string,mixed>,attribution:?array<string,mixed>}
     */
    public static function createManual(array $in, array $actor): array
    {
        $e = [];
        if (!in_array($actor['role'] ?? '', ['owner', 'staff'], true)) {
            return ['errors' => ['form' => 'Tu rol no puede crear reservas.'], 'booking' => null, 'attribution' => null];
        }
        $phone = phone_e164((string) ($in['phone'] ?? ''));
        if ($phone === null) {
            $e['phone'] = 'Escribe un WhatsApp válido.';
        }
        $name = trim(preg_replace('/\s+/u', ' ', (string) ($in['name'] ?? '')) ?? '');
        if (mb_strlen($name) < 3 || mb_strlen($name) > 120 || preg_match('/[<>{}\\\\]/', $name)) {
            $e['name'] = 'Escribe el nombre completo.';
        }
        $email = mb_strtolower(trim((string) ($in['email'] ?? '')));
        if ($email !== '' && (mb_strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            $e['email'] = 'Correo no válido (puedes dejarlo vacío).';
        }
        $birthday = trim((string) ($in['birthday'] ?? ''));
        if ($birthday !== '') {
            $bd = DateTimeImmutable::createFromFormat('!Y-m-d', $birthday);
            if ($bd === false || $bd->format('Y-m-d') !== $birthday || (int) $bd->format('Y') < 1920 || $bd > new DateTimeImmutable('today')) {
                $e['birthday'] = 'Fecha de cumpleaños no válida.';
            }
        }
        $service = (string) ($in['service'] ?? '');
        if (!in_array($service, BookingService::PACKS, true)) {
            $e['service'] = 'Elige el servicio.';
        }
        $people = (int) ($in['people'] ?? 0);
        if ($people < 1 || $people > (int) config('app.booking.max_people', 4)) {
            $e['people'] = 'Elige cuántas personas.';
        }
        $location = (string) ($in['location'] ?? '');
        if (!in_array($location, ['studio', 'home'], true)) {
            $e['location'] = 'Elige el lugar.';
        }
        $address = trim(mb_substr((string) ($in['address'] ?? ''), 0, 255));
        if ($location === 'home' && mb_strlen($address) < 8) {
            $e['address'] = 'Escribe la dirección del domicilio.';
        }
        $date = (string) ($in['date'] ?? '');
        $time = (string) ($in['time'] ?? '');
        $slotErr = self::validateNewSlot($date, $time, true);
        if ($slotErr !== null) {
            $e['date'] = $slotErr;
        }
        $events = (array) config('app.booking.event_types', []);
        $eventType = (string) ($in['event_type'] ?? '');
        if (!array_key_exists($eventType, $events)) {
            $e['event_type'] = 'Elige el tipo de evento.';
        }
        $firstSource = (string) ($in['first_source'] ?? '');
        if (!array_key_exists($firstSource, self::FIRST_SOURCES)) {
            $e['first_source'] = 'Elige cómo llegó la clienta.';
        }
        $leadCode = strtoupper(trim((string) ($in['lead_code'] ?? '')));
        if ($leadCode !== '' && !preg_match('/^WEB-\d{4}-\d{4}$/', $leadCode)) {
            $e['lead_code'] = 'El código debe tener la forma WEB-2611-0087.';
        } elseif ($leadCode !== '' && Db::value('SELECT id FROM leads WHERE code = ?', [$leadCode]) === null) {
            $e['lead_code'] = 'Ese código WEB no existe. Revísalo en el mensaje de WhatsApp.';
        }
        if (empty($in['consent'])) {
            $e['consent'] = 'Confirma que la clienta autorizó el tratamiento de sus datos.';
        }
        $notes = trim(mb_substr((string) ($in['notes'] ?? ''), 0, 1000));
        if ($e !== []) {
            return ['errors' => $e, 'booking' => null, 'attribution' => null];
        }

        $depositReceived = !empty($in['deposit_received']);
        $alert = null;
        try {
            $booking = Db::transaction(static function () use ($phone, $name, $email, $birthday, $address, $service, $people, $location, $date, $time, $eventType, $firstSource, $leadCode, $notes, $depositReceived, $actor, &$alert): array|string {
                Db::run("INSERT IGNORE INTO counters (name, period, value) VALUES ('agenda', '0000', 0)");
                Db::value("SELECT value FROM counters WHERE name = 'agenda' AND period = '0000' FOR UPDATE");
                [$startUtc, $endUtc] = self::interval($date, $time, $people);
                if (self::overlaps($startUtc, $endUtc, null)) {
                    return 'Esa hora se cruza con otra cita. Elige otra.';
                }
                $now = now_utc();
                $existing = Db::one('SELECT * FROM clients WHERE phone_e164 = ? FOR UPDATE', [$phone]);
                $attr = AttributionService::forManualBooking($existing, $leadCode !== '' ? $leadCode : null, $now);
                $isWeb = (bool) $attr['is_web_attributed'];
                $clientSource = $attr['source'] === Source::WHATSAPP_WEB->value ? 'whatsapp_web' : $firstSource;
                $client = ClientRepository::upsert([
                    'phone_e164' => $phone, 'name' => $name, 'email' => $email !== '' ? $email : null,
                    'birthday' => $birthday !== '' ? $birthday : null, 'address' => $address !== '' ? $address : null,
                ], $clientSource, $attr['source'] === Source::WHATSAPP_WEB->value);

                // §9.6: teléfono ya atribuido a la web registrado como origen manual
                if ($existing !== null && $leadCode === '' && $attr['source'] === Source::WEB_RETURNING->value) {
                    $alert = ['client_id' => (int) $existing['id'], 'phone' => $phone, 'until' => $existing['web_attributed_until'], 'chosen' => $firstSource];
                }

                $b = BookingService::insertBooking([
                    'client_id' => $client['id'], 'service' => $service, 'people' => $people, 'date' => $date, 'time' => $time,
                    'location' => $location, 'address' => $location === 'home' ? $address : null, 'event_type' => $eventType,
                    'how_found' => null, 'notes' => $notes !== '' ? $notes : null,
                    'status' => $depositReceived ? 'confirmada' : 'solicitada',
                    'deposit_confirmed_at' => $depositReceived ? $now : null,
                    'source' => $attr['source'], 'is_web_attributed' => $attr['is_web_attributed'],
                    'lead_id' => $attr['lead_id'], 'created_by' => (int) $actor['id'], 'hold' => !$depositReceived,
                ], (int) $actor['id'], $actor['ip'] ?? null, $actor['ua'] ?? null, [
                    'registro' => 'manual', 'codigo_web' => $leadCode !== '' ? $leadCode : null, 'origen_indicado' => $firstSource,
                ]);
                if ($depositReceived) {
                    EventLog::record((int) $b['id'], 'abono_confirmado', null, 'confirmada', null, ['abono' => (int) $b['deposit_amount'], 'al_registrar' => true], (int) $actor['id'], $actor['ip'] ?? null, $actor['ua'] ?? null);
                }
                return ['booking' => $b, 'attr' => $attr, 'isWeb' => $isWeb];
            });
        } catch (\PDOException $e2) {
            app_log('error', 'alta manual: ' . $e2->getMessage());
            return ['errors' => ['form' => 'No se pudo guardar la reserva.'], 'booking' => null, 'attribution' => null];
        }
        if (is_string($booking)) {
            return ['errors' => ['date' => $booking], 'booking' => null, 'attribution' => null];
        }
        $b = $booking['booking'];
        if ($alert !== null) {
            AlertService::raise(
                'telefono_atribuido',
                sprintf('%s se registró a mano como «%s», pero el teléfono %s está atribuido a la web hasta %s. El sistema la marcó como clienta web recurrente.', $b['code'], self::FIRST_SOURCES[$alert['chosen']] ?? $alert['chosen'], mask_phone($alert['phone']), local_time($alert['until'])?->format('Y-m-d') ?? ''),
                'tel-' . $b['code'],
                (int) $b['id'],
                $alert['client_id'],
            );
        }
        if (!empty($b['client_email'])) {
            BookingNotifier::created($b);
        }
        return ['errors' => [], 'booking' => $b, 'attribution' => $booking['attr']];
    }

    /* ----------------------------------------------------------- comprobantes */

    /**
     * Guarda un comprobante subido (§14.3). $file es una entrada de $_FILES.
     * @return string|null error
     */
    public static function storeFile(string $code, array $file, array $actor): ?string
    {
        if (!in_array($actor['role'] ?? '', ['owner', 'staff'], true)) {
            return 'Tu rol no puede subir comprobantes.';
        }
        $b = Db::one('SELECT id FROM bookings WHERE code = ?', [$code]);
        if ($b === null) {
            return 'La reserva no existe.';
        }
        if (!isset($file['error'], $file['tmp_name'], $file['size']) || is_array($file['error'])) {
            return 'Elige un archivo.';
        }
        if ((int) $file['error'] === UPLOAD_ERR_INI_SIZE || (int) $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            return 'El archivo supera 5 MB.';
        }
        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            return 'No se recibió el archivo. Inténtalo de nuevo.';
        }
        $max = (int) config('app.security.upload_max_bytes', 5 * 1024 * 1024);
        $size = (int) $file['size'];
        if ($size <= 0 || $size > $max) {
            return 'El archivo debe pesar menos de 5 MB.';
        }
        $tmp = (string) $file['tmp_name'];
        if (!defined('TESTING') && !is_uploaded_file($tmp)) {
            return 'Archivo no válido.';
        }
        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!isset(self::MAX_UPLOAD_MIMES[$mime])) {
            return 'Solo se aceptan JPG, PNG, WEBP o PDF.';
        }
        $raw = (string) file_get_contents($tmp);
        if ($mime === 'application/pdf') {
            if (!str_starts_with($raw, '%PDF-')) {
                return 'El PDF no es válido.';
            }
            $data = $raw;
        } else {
            $data = self::reencode($raw, $mime);
            if ($data === null) {
                return 'No se pudo procesar la imagen. Intenta con otra captura.';
            }
        }
        $dir = BASE_PATH . '/storage/uploads/comprobantes';
        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }
        $stored = bin2hex(random_bytes(32));
        if (file_put_contents($dir . '/' . $stored, $data, LOCK_EX) === false) {
            return 'No se pudo guardar el archivo.';
        }
        @chmod($dir . '/' . $stored, 0600);
        $sha = hash('sha256', $data);
        $original = mb_substr(preg_replace('/[^\p{L}\p{N}._ -]/u', '', (string) ($file['name'] ?? '')) ?? '', 0, 160);
        $fileId = Db::insert(
            'INSERT INTO booking_files (booking_id, kind, stored_name, original_name, mime, size_bytes, sha256, uploaded_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [(int) $b['id'], 'comprobante_abono', $stored, $original !== '' ? $original : null, $mime, strlen($data), $sha, (int) $actor['id'], now_utc()],
        );
        EventLog::record((int) $b['id'], 'comprobante_subido', null, null, null, ['archivo' => $fileId, 'tipo' => $mime, 'sha256' => $sha], (int) $actor['id'], $actor['ip'] ?? null, $actor['ua'] ?? null);
        return null;
    }

    /** Recodifica la imagen con GD: elimina EXIF, perfiles y cualquier contenido incrustado. */
    private static function reencode(string $raw, string $mime): ?string
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }
        $img = @imagecreatefromstring($raw);
        if ($img === false) {
            return null;
        }
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w < 1 || $h < 1 || $w * $h > 40_000_000) {
            imagedestroy($img);
            return null;
        }
        ob_start();
        $ok = match ($mime) {
            'image/png' => (static function () use ($img): bool {
                imagesavealpha($img, true);
                return imagepng($img, null, 6);
            })(),
            'image/webp' => imagewebp($img, null, 85),
            default => imagejpeg($img, null, 88),
        };
        $out = (string) ob_get_clean();
        imagedestroy($img);
        return $ok && $out !== '' ? $out : null;
    }

    /** @return array{row:array<string,mixed>,path:string}|null */
    public static function fileForDownload(int $id): ?array
    {
        $row = Db::one('SELECT f.*, b.code FROM booking_files f JOIN bookings b ON b.id = f.booking_id WHERE f.id = ?', [$id]);
        if ($row === null || !preg_match('/^[a-f0-9]{64}$/', (string) $row['stored_name'])) {
            return null;
        }
        $path = BASE_PATH . '/storage/uploads/comprobantes/' . $row['stored_name'];
        if (!is_file($path)) {
            return null;
        }
        return ['row' => $row, 'path' => $path];
    }

    public static function logDownload(int $fileId, array $actor, string $code): void
    {
        AuditLog::record((int) $actor['id'], 'comprobante_descargado', $code . '#' . $fileId, null, $actor['ip'] ?? null);
    }

    /* ---------------------------------------------------------------- calendario */

    /**
     * @return array{start:DateTimeImmutable,days:array<string,array{bookings:list<array<string,mixed>>,blocks:list<array<string,mixed>>,holiday:?string}>}
     */
    public static function calendar(int $year, int $month): array
    {
        $tz = Availability::tz();
        $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month), $tz);
        $end = $start->modify('first day of next month');
        $fromUtc = $start->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $toUtc = $end->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $days = [];
        for ($d = $start; $d < $end; $d = $d->modify('+1 day')) {
            $days[$d->format('Y-m-d')] = ['bookings' => [], 'blocks' => [], 'holiday' => Holidays::name($d->format('Y-m-d'))];
        }
        $rows = Db::all(
            BookingService::SELECT . " WHERE b.starts_at >= ? AND b.starts_at < ? AND b.status IN ('solicitada','confirmada','reprogramada','realizada') ORDER BY b.starts_at",
            [$fromUtc, $toUtc],
        );
        foreach ($rows as $r) {
            $k = local_time($r['starts_at'])?->format('Y-m-d');
            if ($k !== null && isset($days[$k])) {
                $days[$k]['bookings'][] = $r;
            }
        }
        foreach (Db::all('SELECT * FROM blocked_slots WHERE starts_at < ? AND ends_at > ? ORDER BY starts_at', [$toUtc, $fromUtc]) as $bl) {
            $s = local_time($bl['starts_at']);
            $en = local_time($bl['ends_at']);
            for ($d = $s->setTime(0, 0); $d < $en && $d < $end; $d = $d->modify('+1 day')) {
                $k = $d->format('Y-m-d');
                if (isset($days[$k])) {
                    $days[$k]['blocks'][] = $bl;
                }
            }
        }
        return ['start' => $start, 'days' => $days];
    }

    /* ------------------------------------------------------------------ apoyo */

    /** @return array{0:string,1:string} inicio y fin en UTC */
    private static function interval(string $date, string $time, int $people): array
    {
        $start = new DateTimeImmutable($date . ' ' . $time, Availability::tz());
        $startUtc = $start->setTimezone(new DateTimeZone('UTC'));
        return [$startUtc->format('Y-m-d H:i:s'), $startUtc->modify('+' . Availability::durationMinutes($people) . ' minutes')->format('Y-m-d H:i:s')];
    }

    private static function validateNewSlot(?string $date, ?string $time, bool $anyTime = false): ?string
    {
        $d = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $date, Availability::tz());
        if ($d === false || $d->format('Y-m-d') !== $date) {
            return 'Elige la fecha.';
        }
        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) $time)) {
            return 'Elige la hora.';
        }
        if (!$anyTime && !in_array($time, Settings::slots(), true)) {
            return 'Elige una de las horas de la agenda.';
        }
        $start = new DateTimeImmutable($date . ' ' . $time, Availability::tz());
        if ($start <= new DateTimeImmutable('now', Availability::tz())) {
            return 'La fecha y hora deben ser futuras.';
        }
        if ($d > Availability::lastDay()) {
            return 'La fecha está demasiado lejos.';
        }
        return null;
    }

    private static function slotFreeAt(DateTimeImmutable $startLocal, int $people, int $ignoreId): bool
    {
        $startUtc = $startLocal->setTimezone(new DateTimeZone('UTC'));
        $endUtc = $startUtc->modify('+' . Availability::durationMinutes($people) . ' minutes');
        return !self::overlaps($startUtc->format('Y-m-d H:i:s'), $endUtc->format('Y-m-d H:i:s'), $ignoreId);
    }

    /** ¿Se cruza con una cita activa o un bloqueo? (alta manual: ignora las reglas de anticipación de la web) */
    private static function overlaps(string $startUtc, string $endUtc, ?int $ignoreId): bool
    {
        $n = (int) Db::value(
            'SELECT COUNT(*) FROM bookings WHERE starts_at < ? AND ends_at > ? AND id <> ? AND ' . Availability::ACTIVE_SQL,
            [$endUtc, $startUtc, $ignoreId ?? 0, now_utc()],
        );
        $n += (int) Db::value('SELECT COUNT(*) FROM blocked_slots WHERE starts_at < ? AND ends_at > ?', [$endUtc, $startUtc]);
        return $n > 0;
    }

    /** Texto legible de un evento del historial. */
    public static function eventLabel(array $ev): string
    {
        $p = is_string($ev['payload'] ?? null) ? (json_decode($ev['payload'], true) ?: []) : [];
        return match ($ev['type']) {
            'creada' => ($p['registro'] ?? '') === 'manual'
                ? 'Reserva registrada en el panel' . (!empty($p['codigo_web']) ? ' con código ' . $p['codigo_web'] : '')
                : 'Reserva creada desde el formulario web',
            'correo_enviado' => 'Correo enviado a la clienta (' . str_replace('-', ' ', (string) ($p['plantilla'] ?? '')) . ')',
            'abono_confirmado' => 'Abono de ' . money((int) ($p['abono'] ?? 0)) . ' confirmado',
            'reprogramada' => 'Reprogramada' . (isset($p['despues']['inicio']) ? ' para el ' . self::shortDate((string) $p['despues']['inicio']) : ''),
            'confirmada' => 'Confirmada con la nueva fecha',
            'realizada' => 'Servicio realizado',
            'no_asistio' => 'Marcada como «No asistió»',
            'cancelada' => 'Reserva cancelada',
            'vencida' => 'Venció sin abono y liberó el cupo',
            'valor_modificado' => 'Valor o servicio modificado' . (isset($p['antes']['total'], $p['despues']['total']) ? ': ' . money((int) $p['antes']['total']) . ' → ' . money((int) $p['despues']['total']) : ''),
            'datos_modificados' => 'Datos de la reserva actualizados',
            'comprobante_subido' => 'Comprobante del abono adjuntado',
            'encuesta_enviada' => 'Encuesta post-servicio enviada',
            'encuesta_respondida' => 'La clienta respondió la encuesta',
            'recordatorio_enviado' => 'Recordatorio de 24 h enviado',
            default => ucfirst(str_replace('_', ' ', (string) $ev['type'])),
        };
    }

    public static function shortDate(string $utc): string
    {
        $d = local_time($utc);
        if ($d === null) {
            return '';
        }
        return (int) $d->format('j') . ' ' . mb_substr(mes_nombre((int) $d->format('n')), 0, 3) . ' · ' . $d->format('G:i');
    }
}
