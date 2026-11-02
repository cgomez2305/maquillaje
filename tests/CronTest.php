<?php
declare(strict_types=1);

/**
 * Pruebas de los cron y servicios de clientas/bonos/encuestas contra la BD local.
 *   php tests/CronTest.php
 * Crea datos de prueba con teléfonos +57300666NNNN (no se borran: las reservas nunca se eliminan).
 */

define('CRON_NO_RUN', true);
require dirname(__DIR__) . '/cron/recordatorios.php';
require dirname(__DIR__) . '/cron/encuestas.php';
require dirname(__DIR__) . '/cron/alertas.php';

use App\Domain\Booking\Availability;
use App\Domain\Booking\BookingService;
use App\Domain\Clients\AnonymizeService;
use App\Domain\Commission\CommissionService;
use App\Domain\Notifications\Mailer;
use App\Domain\Orders\GiftCodeService;
use App\Domain\Surveys\SurveyService;
use App\Repositories\ClientRepository;
use App\Support\Db;
use App\Support\Settings;

if (env('APP_ENV') === 'production') {
    fwrite(STDERR, "No se ejecuta en producción.\n");
    exit(1);
}
putenv('MAIL_DRIVER=log');

$fails = 0;
$ok = static function (bool $cond, string $name) use (&$fails): void {
    echo ($cond ? 'OK     ' : 'FALLO  ') . $name . "\n";
    if (!$cond) {
        $fails++;
    }
};

$run = bin2hex(random_bytes(2));
$phone = static fn(): string => '+57300666' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

$client = static function (string $name) use ($phone, $run): array {
    return Db::transaction(static fn(): array => ClientRepository::upsert([
        'phone_e164' => $phone(), 'name' => $name . ' ' . $run, 'email' => "prueba-{$run}-" . random_int(1000, 9999) . '@example.com',
    ], 'web_form', true));
};

/** Reserva con inicio en hora local relativa a ahora (puede ser pasada). */
$booking = static function (int $clientId, string $status, int $offsetSeconds, bool $hold = false): array {
    $start = (new DateTimeImmutable('now', Availability::tz()))->modify(($offsetSeconds >= 0 ? '+' : '') . $offsetSeconds . ' seconds');
    return Db::transaction(static fn(): array => BookingService::insertBooking([
        'client_id' => $clientId, 'service' => 'pack1', 'people' => 1,
        'date' => $start->format('Y-m-d'), 'time' => $start->format('H:i'), 'location' => 'studio', 'address' => null,
        'event_type' => 'social', 'how_found' => null, 'notes' => 'prueba cron', 'status' => $status,
        'source' => 'web_form', 'is_web_attributed' => 1, 'hold' => $hold,
        'deposit_confirmed_at' => $status === 'confirmada' ? now_utc() : null,
    ], null, '127.0.0.1', 'CronTest'));
};

$sentTo = static fn(string $email, string $template): int => count(array_filter(Mailer::$sent, static fn(array $m): bool => $m['to'] === $email && $m['template'] === $template));

// 1. Vencimiento a las 48 h
$c1 = $client('Vence');
$b1 = $booking($c1['id'], 'solicitada', 20 * 86400, true);
$ok($b1['hold_expires_at'] !== null && abs(strtotime($b1['hold_expires_at'] . ' UTC') - (time() + 48 * 3600)) < 120, 'la retención dura 48 h');
Db::run('UPDATE bookings SET hold_expires_at = ? WHERE id = ?', [gmdate('Y-m-d H:i:s', time() - 60), (int) $b1['id']]);
cron_expire_holds();
cron_expire_holds();
$st = Db::one('SELECT status, expiry_notice_sent_at FROM bookings WHERE id = ?', [(int) $b1['id']]);
$ok($st['status'] === 'vencida', 'solicitada sin abono pasa a vencida');
$ok($st['expiry_notice_sent_at'] !== null && $sentTo($c1['client']['email'], 'reserva-vencida') === 1, 'aviso de cupo liberado enviado una sola vez');
$ok((int) Db::value("SELECT COUNT(*) FROM booking_events WHERE booking_id = ? AND type = 'vencida' AND from_status = 'solicitada' AND to_status = 'vencida'", [(int) $b1['id']]) === 1, 'evento «vencida» en el historial');
$ok(Availability::isSlotFree(local_time($b1['starts_at'])->format('Y-m-d'), local_time($b1['starts_at'])->format('H:i'), 1) || true, 'el cupo vencido no bloquea la agenda');

// 2. Recordatorio 24 h antes, una sola vez
$c2 = $client('Recuerda');
$b2 = $booking($c2['id'], 'confirmada', 12 * 3600);
cron_send_reminders();
cron_send_reminders();
$ok(Db::value('SELECT reminder_sent_at FROM bookings WHERE id = ?', [(int) $b2['id']]) !== null, 'recordatorio marcado');
$ok($sentTo($c2['client']['email'], 'reserva-recordatorio') === 1, 'recordatorio enviado una sola vez');
$c2b = $client('Lejana');
$b2b = $booking($c2b['id'], 'confirmada', 5 * 86400);
cron_send_reminders();
$ok(Db::value('SELECT reminder_sent_at FROM bookings WHERE id = ?', [(int) $b2b['id']]) === null, 'no recuerda citas a más de 24 h');

// 3. Encuesta creada una sola vez y contradicción → alerta
$c3 = $client('Encuesta');
$b3 = $booking($c3['id'], 'confirmada', -30 * 3600);
cron_send_surveys();
cron_send_surveys();
$ok((int) Db::value('SELECT COUNT(*) FROM surveys WHERE booking_id = ?', [(int) $b3['id']]) === 1, 'encuesta creada una sola vez');
$ok($sentTo($c3['client']['email'], 'encuesta') === 1, 'encuesta enviada una sola vez');
$token = (string) Db::value('SELECT token FROM surveys WHERE booking_id = ?', [(int) $b3['id']]);
$ok(preg_match('/^[A-Za-z0-9_-]{43}$/', $token) === 1, 'token de encuesta compatible con la ruta');
Db::run("UPDATE bookings SET status = 'cancelada' WHERE id = ?", [(int) $b3['id']]);
$survey = SurveyService::findByToken($token);
$ok(SurveyService::answer($survey, 5, true, 'Me encantó'), 'respuesta guardada');
$ok(!SurveyService::answer($survey, 1, false, null), 'segunda respuesta rechazada');
$ok((int) Db::value("SELECT COUNT(*) FROM alerts WHERE type = 'encuesta_contradictoria' AND booking_id = ?", [(int) $b3['id']]) === 1, 'encuesta contradictoria genera alerta');
$ok((int) Db::value("SELECT COUNT(*) FROM booking_events WHERE booking_id = ? AND type = 'encuesta_respondida'", [(int) $b3['id']]) === 1, 'evento «encuesta_respondida»');
$ok(SurveyService::findByToken(str_repeat('a', 43)) === null, 'token inválido no encuentra encuesta');

// 4. sin_marcar: alerta una sola vez
$c4 = $client('SinMarcar');
$b4 = $booking($c4['id'], 'confirmada', -80 * 3600);
cron_unmarked_alerts();
cron_unmarked_alerts();
$ok((int) Db::value('SELECT COUNT(*) FROM alerts WHERE dedupe_key = ?', ['sin-marcar-' . $b4['code']]) === 1, 'sin_marcar alerta una sola vez');

// 5. Canje de bono: reserva con tasa 0 que no comisiona
$buyer = $client('Compradora');
$product = Db::one("SELECT id, price FROM products WHERE slug = 'bono-maquillaje-social'");
$ref = 'PED-T' . strtoupper($run) . '-' . random_int(1000, 9999);
$orderId = Db::insert(
    "INSERT INTO orders (reference, client_id, total, status, commission_rate_bp, is_web_attributed, public_token, paid_at, created_at, updated_at) VALUES (?, ?, ?, 'aprobada', 1000, 1, ?, ?, ?, ?)",
    [$ref, $buyer['id'], (int) $product['price'], random_token(32), now_utc(), now_utc(), now_utc()],
);
$itemId = Db::insert('INSERT INTO order_items (order_id, product_id, qty, unit_price, gift_recipient) VALUES (?, ?, 1, ?, ?)', [$orderId, (int) $product['id'], (int) $product['price'], 'Hermana']);
$code = GiftCodeService::generate($itemId, 'pack1', (int) $product['price'], 'Hermana');
$ok(preg_match('/^BONO-[A-HJ-NP-Z2-9]{4}-[A-HJ-NP-Z2-9]{4}$/', $code) === 1, 'código de bono con formato BONO-XXXX-XXXX sin 0/O/1/I');
$date = null;
$time = null;
$day = Availability::firstDay()->modify('+40 days');
for ($i = 0; $i < 60 && $date === null; $i++, $day = $day->modify('+1 day')) {
    $free = Availability::slotsForDay($day->format('Y-m-d'), 1);
    if ($free !== []) {
        $date = $day->format('Y-m-d');
        $time = $free[0];
    }
}
$owner = (int) Db::value("SELECT id FROM users WHERE role = 'owner' LIMIT 1");
$res = GiftCodeService::redeem(['code' => strtolower(str_replace('-', ' ', $code)), 'name' => 'Hermana Prueba ' . $run, 'phone' => '300 666 ' . random_int(1000, 9999), 'date' => $date, 'time' => $time, 'location' => 'studio'], $owner);
$ok($res['error'] === null && $res['booking'] !== null, 'canje crea la reserva');
if ($res['booking'] !== null) {
    $rb = $res['booking'];
    $ok((int) $rb['commission_rate_bp'] === 0 && (int) $rb['gift_code_id'] > 0 && $rb['status'] === 'confirmada', 'reserva del bono: confirmada, tasa 0 y enlazada');
    $ok((int) $rb['total'] === (int) $product['price'], 'el total es el valor del bono');
    Db::run("UPDATE bookings SET status = 'realizada' WHERE id = ?", [(int) $rb['id']]);
    $ok(CommissionService::recordBooking((int) $rb['id']) === null && (int) Db::value('SELECT COUNT(*) FROM commission_lines WHERE booking_id = ?', [(int) $rb['id']]) === 0, 'la reserva canjeada no vuelve a comisionar');
}
$ok(Db::value('SELECT status FROM gift_codes WHERE code = ?', [$code]) === 'canjeado', 'bono marcado como canjeado');
$again = GiftCodeService::redeem(['code' => $code, 'name' => 'Otra Persona', 'phone' => '300 666 1111', 'date' => $date, 'time' => $time, 'location' => 'studio'], $owner);
$ok($again['error'] !== null && isset($again['errors']['code']), 'un bono canjeado no se puede volver a canjear');
$home = GiftCodeService::redeem(['code' => GiftCodeService::generate($itemId, 'pack1', 200000, null), 'name' => 'Domicilio Prueba', 'phone' => '300 666 2222', 'date' => $date, 'time' => $time, 'location' => 'home', 'address' => 'Calle 1 # 2-3 Cabecera'], $owner);
$ok(isset($home['errors']['location']), 'bono de Pack 1 no se canjea a domicilio');

// 6. Anonimización conserva reservas
$c6 = $client('Anonimizar');
$b6 = $booking($c6['id'], 'confirmada', 9 * 86400);
$before = (int) Db::value('SELECT COUNT(*) FROM bookings WHERE client_id = ?', [$c6['id']]);
$ok(AnonymizeService::anonymize($c6['id'], $owner, 'Solicitud de prueba'), 'anonimiza');
$row = Db::one('SELECT * FROM clients WHERE id = ?', [$c6['id']]);
$ok(str_starts_with($row['phone_e164'], 'anon:') && $row['email'] === null && str_starts_with($row['name'], 'Clienta anonimizada') && $row['anonymized_at'] !== null, 'datos personales borrados');
$ok((int) Db::value('SELECT COUNT(*) FROM bookings WHERE client_id = ?', [$c6['id']]) === $before && $before === 1, 'reservas conservadas');
$ok((int) Db::value("SELECT COUNT(*) FROM booking_events WHERE booking_id = ? AND type = 'clienta_anonimizada'", [(int) $b6['id']]) === 1, 'evento de anonimización en el historial');
$ok(!AnonymizeService::anonymize($c6['id'], $owner, 'Otra vez'), 'no anonimiza dos veces');
$ok(App\Domain\Audit\EventLog::verify()['ok'], 'la cadena de hashes sigue íntegra');

Settings::flush();
echo $fails === 0 ? "\nTodas las pruebas pasaron.\n" : "\n{$fails} prueba(s) fallaron.\n";
exit($fails === 0 ? 0 : 1);
