<?php
declare(strict_types=1);

/**
 * ⚠ DATOS DE DEMOSTRACIÓN — SOLO PARA DESARROLLO. NUNCA EN PRODUCCIÓN.
 *
 * Crea clientas ficticias (teléfonos +57300888…), reservas en varios estados en el mes actual
 * y el anterior, tasas de ejemplo aprobadas por ambas partes (si no hay ninguna aprobada),
 * líneas de comisión, un pedido aprobado, métricas y un par de alertas, para ver el panel con datos.
 *
 * Uso: php database/seeds/demo.php
 * Es idempotente: si ya existen las clientas de demostración, no hace nada. Las reservas y el
 * historial no se pueden borrar (triggers de solo inserción): para empezar de cero, recrea la BD local.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__, 2) . '/app/bootstrap.php';

use App\Domain\Alerts\AlertService;
use App\Domain\Audit\EventLog;
use App\Domain\Booking\BookingService;
use App\Domain\Commission\CommissionService;
use App\Repositories\ClientRepository;
use App\Support\Counter;
use App\Support\Db;

if (config('app.env') === 'production') {
    fwrite(STDERR, "Este sembrado es solo para desarrollo. APP_ENV=production: no se ejecuta.\n");
    exit(1);
}

if (Db::value("SELECT COUNT(*) FROM clients WHERE phone_e164 LIKE '+57300888%'") > 0) {
    echo "Los datos de demostración ya existen. Nada que hacer.\n";
    exit(0);
}

$tz = new DateTimeZone((string) config('app.timezone'));
$now = new DateTimeImmutable('now', $tz);
$thisMonth = $now->modify('first day of this month');
$lastMonth = $thisMonth->modify('-1 month');

// Tasas de ejemplo (solo si no hay ninguna aprobada por ambas partes)
if (!Db::value('SELECT 1 FROM commission_rates WHERE approved_by_owner_at IS NOT NULL AND approved_by_partner_at IS NOT NULL')) {
    $from = $lastMonth->modify('-1 month')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    foreach (['service' => 1000, 'course' => 1500, 'product' => 1000, 'gift' => 1000] as $cat => $bp) {
        Db::run(
            'INSERT INTO commission_rates (category, rate_bp, valid_from, approved_by_owner_at, approved_by_partner_at, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [$cat, $bp, $from, $from, $from, $from],
        );
    }
    echo "Tasas de ejemplo aprobadas: servicios 10 %, cursos 15 %, tienda y bonos 10 %.\n";
}

$people = [
    ['Laura Martínez', 'laura.demo@example.com'], ['Daniela Rojas', 'daniela.demo@example.com'], ['Camila Ortiz', 'camila.demo@example.com'],
    ['Valentina Pérez', 'valentina.demo@example.com'], ['Juliana Díaz', 'juliana.demo@example.com'], ['Sara Gómez', 'sara.demo@example.com'],
    ['Natalia Suárez', 'natalia.demo@example.com'], ['Mariana López', 'mariana.demo@example.com'], ['Paula Herrera', 'paula.demo@example.com'],
    ['Andrea Castro', 'andrea.demo@example.com'],
];

// [cliente, servicio, personas, fecha (mes, día), hora, lugar, evento, origen, estado final]
$plan = [
    [0, 'pack3', 1, [$lastMonth, 4], '07:00', 'studio', 'boda', 'web_form', 'realizada'],
    [1, 'pack1', 3, [$lastMonth, 9], '09:00', 'studio', 'grado', 'whatsapp_web', 'realizada'],
    [2, 'pack2', 1, [$lastMonth, 14], '14:00', 'home', 'social', 'web_form', 'cancelada'],
    [3, 'pack2', 1, [$lastMonth, 18], '11:00', 'home', 'social', 'google_business', 'realizada'],
    [4, 'pack1', 1, [$lastMonth, 22], '16:00', 'studio', 'grado', 'web_form', 'no_asistio'],
    [5, 'pack3', 1, [$lastMonth, 27], '05:00', 'studio', 'quince', 'web_form', 'realizada'],
    [6, 'pack1', 1, [$lastMonth, 29], '09:00', 'studio', 'social', 'manual', 'realizada'],
    [7, 'pack1', 1, [$thisMonth, 1], '09:00', 'studio', 'grado', 'web_form', 'realizada'],
    [0, 'pack1', 2, [$thisMonth, 21], '07:00', 'studio', 'boda', 'web_returning', 'confirmada'],
    [8, 'pack3', 1, [$thisMonth, 23], '09:00', 'studio', 'boda', 'web_form', 'confirmada'],
    [9, 'pack2', 1, [$thisMonth, 25], '14:00', 'home', 'social', 'google_business', 'solicitada'],
    [3, 'pack3', 1, [$thisMonth, 28], '05:00', 'studio', 'quince', 'whatsapp_web', 'confirmada'],
];

// El periodo del mes anterior existe antes de registrar sus servicios (si no, entrarían como ajuste)
CommissionService::ensurePeriod((int) $lastMonth->format('Y'), (int) $lastMonth->format('n'));

$owner = Db::value("SELECT id FROM users WHERE role = 'owner' ORDER BY id LIMIT 1");
$ownerId = $owner !== null ? (int) $owner : null;
$clients = [];
$created = 0;

foreach ($plan as [$ci, $svc, $ppl, [$month, $day], $time, $loc, $event, $source, $final]) {
    $date = $month->setDate((int) $month->format('Y'), (int) $month->format('n'), $day)->format('Y-m-d');
    $isWeb = $source !== 'manual';
    $booking = Db::transaction(static function () use ($ci, $people, &$clients, $svc, $ppl, $date, $time, $loc, $event, $source, $isWeb, $ownerId): array {
        if (!isset($clients[$ci])) {
            $c = ClientRepository::upsert([
                'phone_e164' => sprintf('+5730088800%02d', $ci),
                'name' => $people[$ci][0],
                'email' => $people[$ci][1],
                'address' => $loc === 'home' ? 'Calle 48 #27-15, Cabecera, Bucaramanga' : null,
            ], $isWeb ? ($source === 'google_business' ? 'google_business' : 'web_form') : 'manual_other', $isWeb);
            $clients[$ci] = $c['id'];
        }
        return BookingService::insertBooking([
            'client_id' => $clients[$ci], 'service' => $svc, 'people' => $ppl, 'date' => $date, 'time' => $time,
            'location' => $loc, 'address' => $loc === 'home' ? 'Calle 48 #27-15, Cabecera, Bucaramanga' : null,
            'event_type' => $event, 'how_found' => 'google', 'notes' => 'Reserva de demostración',
            'status' => 'solicitada', 'source' => $source, 'is_web_attributed' => $isWeb ? 1 : 0,
            'created_by' => $source === 'web_form' || $source === 'google_business' ? null : $ownerId, 'hold' => true,
        ], $source === 'web_form' || $source === 'google_business' ? null : $ownerId, '127.0.0.1', 'seed-demo', ['demo' => true]);
    });
    $id = (int) $booking['id'];
    $t = now_utc();

    if ($final !== 'solicitada') {
        if ($final === 'cancelada') {
            Db::run("UPDATE bookings SET status = 'cancelada', hold_expires_at = NULL, updated_at = ? WHERE id = ?", [$t, $id]);
            EventLog::record($id, 'cancelada', 'solicitada', 'cancelada', 'La clienta desistió', null, $ownerId);
        } else {
            Db::run("UPDATE bookings SET status = 'confirmada', deposit_confirmed_at = ?, hold_expires_at = NULL, updated_at = ? WHERE id = ?", [$t, $t, $id]);
            EventLog::record($id, 'abono_confirmado', 'solicitada', 'confirmada', null, ['abono' => (int) $booking['deposit_amount']], $ownerId);
            if (in_array($final, ['realizada', 'no_asistio'], true)) {
                Db::run('UPDATE bookings SET status = ?, updated_at = ? WHERE id = ?', [$final, $t, $id]);
                EventLog::record($id, $final, 'confirmada', $final, $final === 'no_asistio' ? 'No se presentó ni avisó' : null, null, $ownerId);
                CommissionService::recordBooking($id);
            }
        }
    }
    $created++;
}

// Pedido aprobado de un curso (venta en línea)
$product = Db::one("SELECT id, price FROM products WHERE slug = 'curso-automaquillaje'");
if ($product !== null) {
    $paidLocal = $thisMonth->setDate((int) $thisMonth->format('Y'), (int) $thisMonth->format('n'), 1)->setTime(16, 20);
    $paid = $paidLocal->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    Db::transaction(static function () use ($product, $clients, $paid): void {
        $ref = Counter::code('PED');
        $rate = (int) (Db::value("SELECT rate_bp FROM commission_rates WHERE category = 'course' AND approved_by_owner_at IS NOT NULL AND approved_by_partner_at IS NOT NULL ORDER BY valid_from DESC LIMIT 1") ?? 0);
        $oid = Db::insert(
            "INSERT INTO orders (reference, client_id, total, status, wompi_transaction_id, commission_rate_bp, is_web_attributed, public_token, paid_at, created_at, updated_at)
             VALUES (?, ?, ?, 'aprobada', ?, ?, 1, ?, ?, ?, ?)",
            [$ref, $clients[7], (int) $product['price'], '12345-1700000000-00001', $rate, random_token(32), $paid, $paid, $paid],
        );
        Db::run('INSERT INTO order_items (order_id, product_id, qty, unit_price) VALUES (?, ?, 1, ?)', [$oid, (int) $product['id'], (int) $product['price']]);
        CommissionService::recordOrder($oid);
    });
}

// Métricas de los dos meses
foreach ([$lastMonth, $thisMonth] as $m) {
    $days = (int) $m->format('t');
    for ($d = 1; $d <= $days; $d++) {
        $day = $m->setDate((int) $m->format('Y'), (int) $m->format('n'), $d);
        if ($day > $now) {
            break;
        }
        foreach (['visits' => random_int(25, 60), 'whatsapp_clicks' => random_int(1, 5), 'booking_starts' => random_int(0, 3)] as $metric => $v) {
            Db::run('INSERT INTO metrics_daily (day, metric, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)', [$day->format('Y-m-d'), $metric, $v]);
        }
    }
}

// Alertas de ejemplo
$cancel = Db::one("SELECT b.id, b.code, b.client_id FROM bookings b JOIN clients c ON c.id = b.client_id WHERE c.phone_e164 LIKE '+57300888%' AND b.status = 'cancelada' LIMIT 1");
if ($cancel) {
    AlertService::raise('cancelacion_web', $cancel['code'] . ' se canceló. Motivo: «La clienta desistió».', 'demo-cancel-' . $cancel['id'], (int) $cancel['id'], (int) $cancel['client_id']);
}
$noshow = Db::one("SELECT b.id, b.code, b.client_id FROM bookings b JOIN clients c ON c.id = b.client_id WHERE c.phone_e164 LIKE '+57300888%' AND b.status = 'no_asistio' LIMIT 1");
if ($noshow) {
    AlertService::raise('cancelacion_web', $noshow['code'] . ' se marcó como «No asistió». El abono retenido comisiona.', 'demo-noshow-' . $noshow['id'], (int) $noshow['id'], (int) $noshow['client_id']);
}

// Como el día 1: el mes anterior pasa a aprobación
CommissionService::startApproval();

echo "Datos de demostración creados: {$created} reservas, 1 pedido, métricas y alertas.\n";
