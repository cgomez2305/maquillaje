<?php
declare(strict_types=1);

/**
 * Pruebas del dominio de comisiones (§9.4, §9.5) contra la BD local.
 *   php tests/CommissionTest.php
 * Todo corre dentro de una transacción que se revierte al final: no deja datos.
 * Usa periodos del año 2090 para no mezclarse con los reales. Teléfonos de prueba +57300777…
 */

if (PHP_SAPI !== 'cli') {
    exit(1);
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Domain\Commission\CommissionService;
use App\Domain\Commission\RateService;
use App\Support\Db;

if (config('app.env') === 'production') {
    fwrite(STDERR, "No se ejecuta en producción.\n");
    exit(1);
}

$fails = 0;
$total = 0;
function check(string $name, bool $ok, string $detail = ''): void
{
    global $fails, $total;
    $total++;
    if (!$ok) {
        $fails++;
    }
    echo ($ok ? 'OK    ' : 'FALLO ') . $name . ($ok || $detail === '' ? '' : " — {$detail}") . "\n";
}

/** Ejecuta $fn y devuelve true si la BD la rechaza (trigger SIGNAL 45000). */
function rejected(callable $fn): bool
{
    try {
        $fn();
        return false;
    } catch (PDOException $e) {
        return $e->getCode() === '45000' || str_contains($e->getMessage(), '45000');
    }
}

$pdo = Db::pdo();
$pdo->beginTransaction();

try {
    $now = now_utc();
    $serviceId = (int) Db::value("SELECT id FROM services WHERE code = 'pack3'");
    $phone = '+57300777' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    $clientId = Db::insert(
        "INSERT INTO clients (phone_e164, name, email, first_source, first_seen_at, consent_data_at, created_at, updated_at) VALUES (?, 'Prueba Comisiones', 'prueba@example.com', 'web_form', ?, ?, ?, ?)",
        [$phone, $now, $now, $now, $now],
    );

    /** Crea una reserva de prueba con los valores indicados. */
    $mk = static function (string $startsUtc, string $status, int $total, int $rate, int $web = 1, ?string $deposit = null, ?int $gift = null) use ($clientId, $serviceId, $now): int {
        return Db::insert(
            'INSERT INTO bookings (code, public_token, client_id, service_id, people, starts_at, ends_at, location, unit_price, total, deposit_amount,
                                   deposit_confirmed_at, status, source, is_web_attributed, commission_rate_bp, gift_code_id, created_at, updated_at)
             VALUES (?, ?, ?, ?, 1, ?, ?, \'studio\', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                'T' . str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT), random_token(32), $clientId, $serviceId,
                $startsUtc, $startsUtc, $total, $total, intdiv($total, 2), $deposit, $status, $web ? 'web_form' : 'manual', $web, $rate, $gift, $now, $now,
            ],
        );
    };
    $line = static fn(string $ref): ?array => Db::one('SELECT l.*, p.year, p.month FROM commission_lines l JOIN commission_periods p ON p.id = l.period_id WHERE ref = ?', [$ref]);

    // Periodos de prueba aislados (2090)
    $jan = CommissionService::ensurePeriod(2090, 1);
    $feb = CommissionService::ensurePeriod(2090, 2);

    // 1. Fórmula: amount = intdiv(base * rate_bp, 10000)
    check('fórmula intdiv con base y tasa', RateService::amount(600000, 1000) === 60000 && RateService::amount(333333, 1250) === 41666 && RateService::amount(199999, 1) === 19);

    // 2. Realizada → línea por el total con la tasa guardada en la reserva
    $b1 = $mk('2090-02-10 14:00:00', 'realizada', 600000, 1000);
    CommissionService::recordBooking($b1);
    $l1 = $line("B{$b1}-servicio");
    check('realizada comisiona el total', $l1 !== null && (int) $l1['base'] === 600000 && (int) $l1['amount'] === 60000 && (int) $l1['month'] === 2);

    // 3. Idempotencia por ref
    CommissionService::recordBooking($b1);
    check('idempotente por ref', (int) Db::value('SELECT COUNT(*) FROM commission_lines WHERE ref = ?', ["B{$b1}-servicio"]) === 1);

    // 4. No comisionan: cancelada, vencida, fuera de la web, canje de bono
    $none = [
        'cancelada' => $mk('2090-02-11 14:00:00', 'cancelada', 600000, 1000),
        'vencida' => $mk('2090-02-12 14:00:00', 'vencida', 600000, 1000),
        'no web' => $mk('2090-02-13 14:00:00', 'realizada', 600000, 0, 0),
        'canje de bono' => $mk('2090-02-14 14:00:00', 'realizada', 200000, 0, 1, null, 999999),
    ];
    foreach ($none as $label => $id) {
        CommissionService::recordBooking($id);
        check("no comisiona: {$label}", (int) Db::value('SELECT COUNT(*) FROM commission_lines WHERE booking_id = ?', [$id]) === 0);
    }

    // 5. No asistió con abono confirmado → base = abono
    $b5 = $mk('2090-02-15 14:00:00', 'no_asistio', 250000, 1000, 1, $now);
    CommissionService::recordBooking($b5);
    $l5 = $line("B{$b5}-no_asistio");
    check('no asistió con abono comisiona el abono', $l5 !== null && (int) $l5['base'] === 125000 && (int) $l5['amount'] === 12500);
    $b5b = $mk('2090-02-16 14:00:00', 'no_asistio', 250000, 1000, 1, null);
    CommissionService::recordBooking($b5b);
    check('no asistió sin abono no comisiona', (int) Db::value('SELECT COUNT(*) FROM commission_lines WHERE booking_id = ?', [$b5b]) === 0);

    // 6. Ajuste tardío: el periodo del servicio ya no está abierto → entra al abierto actual con nota
    Db::run("UPDATE commission_periods SET status = 'en_aprobacion' WHERE id = ?", [(int) $jan['id']]);
    $b6 = $mk('2090-01-20 14:00:00', 'realizada', 200000, 1000);
    CommissionService::recordBooking($b6);
    $l6 = $line("B{$b6}-servicio");
    $cur = local_time(now_utc());
    check(
        'ajuste tardío al periodo abierto con nota',
        $l6 !== null && (int) $l6['year'] === (int) $cur->format('Y') && (int) $l6['month'] === (int) $cur->format('n') && str_contains((string) $l6['note'], 'enero 2090'),
        json_encode($l6),
    );
    Db::run('DELETE FROM commission_lines WHERE id = ?', [(int) $l6['id']]); // no dejar rastro en el periodo real (igual se revierte)

    // 7. startApproval mueve el mes anterior a «en_aprobacion»
    $moved = CommissionService::startApproval(new DateTimeImmutable('2090-03-01 00:10:00', new DateTimeZone('America/Bogota')));
    $febNow = CommissionService::period(2090, 2);
    check('startApproval mueve el mes anterior', in_array((int) $feb['id'], $moved, true) && $febNow['status'] === 'en_aprobacion');
    check('startApproval abre el mes en curso', (CommissionService::period(2090, 3)['status'] ?? null) === 'abierto');

    // 8. Aprobación doble: cierra y guarda el hash
    check('aprobar un periodo abierto falla', CommissionService::approve((int) CommissionService::period(2090, 3)['id'], 'owner') !== null);
    CommissionService::approve((int) $feb['id'], 'owner');
    check('con una aprobación sigue en aprobación', CommissionService::period(2090, 2)['status'] === 'en_aprobacion');
    check('el staff no aprueba', CommissionService::approve((int) $feb['id'], 'staff') !== null);
    CommissionService::approve((int) $feb['id'], 'partner');
    $closed = CommissionService::period(2090, 2);
    check('con las dos aprobaciones se cierra', $closed['status'] === 'cerrado' && $closed['snapshot_hash'] !== null);
    check('hash de las líneas verificable', CommissionService::verifySnapshot((int) $feb['id']) === true);
    check('totales del periodo cerrado', (int) $closed['total_commission'] === 72500 && (int) $closed['total_base'] === 725000, (string) $closed['total_commission']);

    // 9. Corte cerrado inalterable (triggers)
    check('no se puede modificar una línea de un periodo cerrado', rejected(static fn() => Db::run('UPDATE commission_lines SET amount = 1 WHERE id = ?', [(int) $l1['id']])));
    check('no se puede borrar una línea de un periodo cerrado', rejected(static fn() => Db::run('DELETE FROM commission_lines WHERE id = ?', [(int) $l1['id']])));
    check('no se puede insertar en un periodo cerrado', rejected(static fn() => Db::run(
        "INSERT INTO commission_lines (period_id, kind, base, rate_bp, amount, ref, created_at) VALUES (?, 'ajuste', 1, 1, 0, ?, ?)",
        [(int) $feb['id'], 'T-' . random_token(8), now_utc()],
    )));
    check('no se puede cambiar el total de un periodo cerrado', rejected(static fn() => Db::run('UPDATE commission_periods SET total_commission = 1 WHERE id = ?', [(int) $feb['id']])));
    check('no se puede reabrir un periodo cerrado', rejected(static fn() => Db::run("UPDATE commission_periods SET status = 'abierto' WHERE id = ?", [(int) $feb['id']])));
    check('no se puede borrar un periodo', rejected(static fn() => Db::run('DELETE FROM commission_periods WHERE id = ?', [(int) $feb['id']])));
    check('marcar pagado exige nota', CommissionService::markPaid((int) $feb['id'], '') !== null);
    check('marcar pagado con nota', CommissionService::markPaid((int) $feb['id'], '5 mar · transferencia') === null && CommissionService::period(2090, 2)['status'] === 'pagado');
    check('un periodo pagado ya no cambia', rejected(static fn() => Db::run('UPDATE commission_periods SET payment_note = ? WHERE id = ?', ['x', (int) $feb['id']])));
    check('el hash sigue verificando tras pagar', CommissionService::verifySnapshot((int) $feb['id']) === true);

    // 10. Pedido aprobado y reembolso negativo en el periodo en que ocurre
    $oid = Db::insert(
        "INSERT INTO orders (reference, client_id, total, status, commission_rate_bp, is_web_attributed, public_token, paid_at, created_at, updated_at)
         VALUES (?, ?, 400000, 'aprobada', 1500, 1, ?, ?, ?, ?)",
        ['PEDT-' . random_int(100000, 999999), $clientId, random_token(32), $now, $now, $now],
    );
    CommissionService::recordOrder($oid);
    $lo = $line("O{$oid}-pedido");
    check('pedido aprobado comisiona', $lo !== null && (int) $lo['amount'] === 60000);
    Db::run("UPDATE orders SET status = 'reembolsada', refunded_at = ? WHERE id = ?", [$now, $oid]);
    CommissionService::recordRefund($oid);
    $lr = $line("O{$oid}-reembolso");
    check('reembolso genera línea negativa', $lr !== null && (int) $lr['base'] === -400000 && (int) $lr['amount'] === -60000, json_encode($lr));
    check('reembolso negativo con intdiv simétrico', RateService::amount(-333333, 1250) === -41666);

    // 11. Tasas: solo rige con doble aprobación
    $t = '2094-01-01 00:00:00';
    Db::run("INSERT INTO commission_rates (category, rate_bp, valid_from, approved_by_owner_at, approved_by_partner_at, created_at) VALUES ('gift', 1100, ?, ?, ?, ?)", [$t, $t, $t, $t]);
    Db::run("INSERT INTO commission_rates (category, rate_bp, valid_from, approved_by_owner_at, created_at) VALUES ('gift', 2222, '2094-06-01 00:00:00', ?, ?)", [$t, $t]);
    check('tasa con doble aprobación rige', RateService::current('gift', '2095-01-01 00:00:00') === 1100);
    check('tasa sin doble aprobación no rige', RateService::current('gift', '2095-01-01 00:00:00') !== 2222);
    check('tasa futura aún no rige', RateService::current('gift', '2093-12-31 00:00:00') !== 1100);
    $rid = (int) Db::value("SELECT id FROM commission_rates WHERE rate_bp = 1100 AND category = 'gift' AND valid_from = ?", [$t]);
    check('una tasa aprobada no se modifica', rejected(static fn() => Db::run('UPDATE commission_rates SET rate_bp = 1 WHERE id = ?', [$rid])));
} catch (Throwable $e) {
    $fails++;
    echo 'FALLO excepción: ' . $e->getMessage() . ' (' . basename($e->getFile()) . ':' . $e->getLine() . ")\n";
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo "\n" . ($total - $fails) . "/{$total} pruebas correctas" . ($fails ? " · {$fails} FALLOS" : '') . "\n";
exit($fails > 0 ? 1 : 0);
