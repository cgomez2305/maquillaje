<?php
declare(strict_types=1);

/**
 * Pruebas del dominio de reservas del panel (§9.1, §9.3, §9.6) contra la BD local.
 *   php tests/BookingAdminTest.php
 * Crea sus propios datos (teléfonos +57300999…) y nunca borra nada (las reservas no se eliminan).
 */

if (PHP_SAPI !== 'cli') {
    exit;
}
define('TESTING', true);
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Domain\Audit\EventLog;
use App\Domain\Booking\Availability;
use App\Domain\Booking\BookingAdminService;
use App\Domain\Booking\BookingService;
use App\Repositories\ClientRepository;
use App\Support\Db;

$fails = 0;
$check = static function (string $name, bool $ok, string $detail = '') use (&$fails): void {
    echo ($ok ? 'OK    ' : 'FALLO ') . $name . ($ok || $detail === '' ? '' : ' → ' . $detail) . "\n";
    if (!$ok) {
        $fails++;
    }
};

$uid = static fn(string $email): int => (int) Db::value('SELECT id FROM users WHERE email = ?', [$email]);
$owner = ['id' => $uid('duena-a@example.com'), 'role' => 'owner', 'ip' => '127.0.0.1', 'ua' => 'test'];
$staff = ['id' => $uid('asistente-a@example.com'), 'role' => 'staff', 'ip' => '127.0.0.1', 'ua' => 'test'];
$partner = ['id' => $uid('socio-a@example.com'), 'role' => 'partner', 'ip' => '127.0.0.1', 'ua' => 'test'];
if (!$owner['id'] || !$staff['id']) {
    fwrite(STDERR, "Faltan los usuarios de prueba duena-a / asistente-a.\n");
    exit(2);
}

$tz = Availability::tz();
$phone = static fn(): string => '+57300999' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);

/** Fecha futura libre a las 09:00 (entre 200 y 500 días) */
$freeDate = static function () use ($tz): string {
    for ($i = 0; $i < 200; $i++) {
        $d = (new DateTimeImmutable('now', $tz))->modify('+' . random_int(200, 500) . ' days');
        $ymd = $d->format('Y-m-d');
        if (Availability::isSlotFree($ymd, '09:00', 1) && Availability::isSlotFree($ymd, '14:00', 1)) {
            return $ymd;
        }
    }
    throw new RuntimeException('Sin fechas libres');
};

/** Crea una reserva directamente (como si viniera de la web) */
$make = static function (string $date, string $time = '09:00', int $web = 1, string $status = 'solicitada') use ($phone): array {
    return Db::transaction(static function () use ($date, $time, $web, $status, $phone): array {
        $c = ClientRepository::upsert(['phone_e164' => $phone(), 'name' => 'Prueba Panel', 'email' => 'prueba-panel@example.com'], 'web_form', (bool) $web);
        return BookingService::insertBooking([
            'client_id' => $c['id'], 'service' => 'pack2', 'people' => 1, 'date' => $date, 'time' => $time,
            'location' => 'studio', 'address' => null, 'event_type' => 'social', 'how_found' => null, 'notes' => null,
            'status' => $status, 'source' => $web ? 'web_form' : 'manual', 'is_web_attributed' => $web, 'lead_id' => null,
            'created_by' => null, 'hold' => $status === 'solicitada',
        ], null, '127.0.0.1', 'test');
    });
};

$alertExists = static fn(string $type, int $bookingId): bool => (bool) Db::value('SELECT 1 FROM alerts WHERE type = ? AND booking_id = ?', [$type, $bookingId]);

// 1. Transiciones inválidas y válidas
$d1 = $freeDate();
$b = $make($d1);
$check('solicitada → realizada no está permitida', BookingAdminService::transition($b['code'], 'realizada', null, null, null, $owner) !== null);
$check('el socio no puede cambiar estados', BookingAdminService::transition($b['code'], 'confirmada', null, null, null, $partner) !== null);
$check('la asistente no puede cancelar', BookingAdminService::transition($b['code'], 'cancelada', 'x', null, null, $staff) !== null);
$err = BookingAdminService::transition($b['code'], 'confirmada', null, null, null, $staff);
$row = BookingService::find((int) $b['id']);
$check('confirmar abono (asistente)', $err === null && $row['status'] === 'confirmada' && $row['deposit_confirmed_at'] !== null && $row['hold_expires_at'] === null, (string) $err);

// 2. Motivo obligatorio y realizada antes de la fecha
$check('cancelar sin motivo se rechaza', BookingAdminService::transition($b['code'], 'cancelada', '  ', null, null, $owner) !== null);
$check('no asistió sin motivo se rechaza', BookingAdminService::transition($b['code'], 'no_asistio', '', null, null, $owner) !== null);
$check('realizada antes de la fecha se rechaza', BookingAdminService::transition($b['code'], 'realizada', null, null, null, $owner) !== null);

// 3. Reprogramar mantiene el código
$newDate = (new DateTimeImmutable($d1, $tz))->modify('+1 day')->format('Y-m-d');
if (!Availability::isSlotFree($newDate, '14:00', 1)) {
    $newDate = $freeDate();
}
$err = BookingAdminService::transition($b['code'], 'reprogramada', 'La clienta cambió el evento', $newDate, '14:00', $owner);
$row = BookingService::find((int) $b['id']);
$check('reprogramar mantiene código y queda confirmada', $err === null && $row['code'] === $b['code'] && $row['status'] === 'confirmada' && local_time($row['starts_at'])->format('Y-m-d H:i') === $newDate . ' 14:00', (string) $err);
$types = array_column(EventLog::forBooking((int) $b['id']), 'type');
$check('reprogramar deja eventos reprogramada + confirmada', in_array('reprogramada', $types, true) && in_array('confirmada', $types, true));
$check('reprogramar a hora ocupada se rechaza', (function () use ($make, $b, $owner, $newDate): bool {
    $other = $make($newDate, '09:00');
    return BookingAdminService::transition($b['code'], 'reprogramada', null, $newDate, '09:00', $owner) !== null;
})());

// 4. Edición de valor después de confirmada
$check('cambiar valor confirmada sin motivo se rechaza', BookingAdminService::update($b['code'], ['total' => '300000', 'reason' => ''], $owner) !== null);
$err = BookingAdminService::update($b['code'], ['total' => '300000', 'reason' => 'Se sumó peinado'], $owner);
$row = BookingService::find((int) $b['id']);
$types = array_column(EventLog::forBooking((int) $b['id']), 'type');
$check('cambiar valor con motivo guarda y registra evento', $err === null && (int) $row['total'] === 300000 && in_array('valor_modificado', $types, true), (string) $err);
$check('cambiar valor genera alerta cambio_valor', $alertExists('cambio_valor', (int) $b['id']));
$check('el socio no puede editar', BookingAdminService::update($b['code'], ['notes' => 'x'], $partner) !== null);

// 5. Cancelar una reserva web genera alerta
$err = BookingAdminService::transition($b['code'], 'cancelada', 'La clienta desistió', null, null, $owner);
$check('cancelar con motivo', $err === null && BookingService::find((int) $b['id'])['status'] === 'cancelada', (string) $err);
$check('cancelar reserva web genera alerta cancelacion_web', $alertExists('cancelacion_web', (int) $b['id']));
$check('una cancelada no admite más cambios', BookingAdminService::transition($b['code'], 'confirmada', null, null, null, $owner) !== null);

// 6. Realizada en una reserva pasada → línea de comisión
$past = (new DateTimeImmutable('now', $tz))->modify('-' . random_int(3, 25) . ' days')->format('Y-m-d');
$p = $make($past, sprintf('%02d:%02d', random_int(5, 20), random_int(0, 3) * 15), 1, 'solicitada');
BookingAdminService::transition($p['code'], 'confirmada', null, null, null, $owner);
$err = BookingAdminService::transition($p['code'], 'realizada', null, null, null, $owner);
$check('marcar realizada después de la fecha', $err === null && BookingService::find((int) $p['id'])['status'] === 'realizada', (string) $err);
$check('realizada genera línea de comisión', (bool) Db::value('SELECT 1 FROM commission_lines WHERE ref = ?', ['B' . $p['id'] . '-servicio']));
$check('realizada ya no permite editar el valor', BookingAdminService::update($p['code'], ['total' => '1', 'reason' => 'x'], $owner) !== null);

// 7. Origen inmutable (trigger)
try {
    Db::run("UPDATE bookings SET source = 'manual' WHERE id = ?", [(int) $p['id']]);
    $check('cambiar source desde SQL lo bloquea el trigger', false, 'no lanzó error');
} catch (PDOException) {
    $check('cambiar source desde SQL lo bloquea el trigger', true);
}
try {
    Db::run('UPDATE bookings SET is_web_attributed = 0 WHERE id = ?', [(int) $p['id']]);
    $check('cambiar is_web_attributed lo bloquea el trigger', false, 'no lanzó error');
} catch (PDOException) {
    $check('cambiar is_web_attributed lo bloquea el trigger', true);
}

// 8. Alta manual: código WEB y clienta recurrente
$lead = App\Domain\Leads\LeadService::create('whatsapp_click', ['origin' => 'test']);
$d2 = $freeDate();
$base = ['name' => 'Clienta Manual', 'service' => 'pack1', 'people' => 1, 'location' => 'studio', 'date' => $d2, 'time' => '09:00', 'event_type' => 'grado', 'first_source' => 'instagram', 'consent' => true];
$r = BookingAdminService::createManual($base + ['phone' => '3009998' . random_int(100, 999), 'lead_code' => $lead['code']], $staff);
$check('alta manual con código WEB → whatsapp_web atribuida', $r['booking'] !== null && $r['booking']['source'] === 'whatsapp_web' && (int) $r['booking']['is_web_attributed'] === 1, json_encode($r['errors']));

$webPhone = $phone();
$make($freeDate(), '09:00', 1); // otra clienta
$wc = Db::transaction(static fn() => ClientRepository::upsert(['phone_e164' => $webPhone, 'name' => 'Clienta Web'], 'web_form', true));
$r2 = BookingAdminService::createManual(['phone' => $webPhone, 'lead_code' => ''] + array_replace($base, ['time' => '14:00', 'first_source' => 'manual_other']), $owner);
$check('teléfono con ventana web → web_returning atribuida', $r2['booking'] !== null && $r2['booking']['source'] === 'web_returning' && (int) $r2['booking']['is_web_attributed'] === 1, json_encode($r2['errors']));
$check('origen manual sobre teléfono atribuido genera alerta', $r2['booking'] !== null && $alertExists('telefono_atribuido', (int) $r2['booking']['id']));
$r3 = BookingAdminService::createManual(['phone' => $phone(), 'lead_code' => ''] + array_replace($base, ['date' => $freeDate()]), $owner);
$check('teléfono nuevo sin código → manual sin atribución', $r3['booking'] !== null && $r3['booking']['source'] === 'manual' && (int) $r3['booking']['is_web_attributed'] === 0, json_encode($r3['errors']));
$r4 = BookingAdminService::createManual(['phone' => $phone(), 'lead_code' => ''] + $base, $owner);
$check('alta manual en hora ocupada se rechaza', $r4['booking'] === null && isset($r4['errors']['date']));
$r5 = BookingAdminService::createManual(['phone' => '123', 'lead_code' => 'WEB-9999-9999'] + array_replace($base, ['consent' => false]), $owner);
$check('alta manual valida teléfono, código y autorización', isset($r5['errors']['phone'], $r5['errors']['lead_code'], $r5['errors']['consent']));
$check('el socio no puede crear reservas', BookingAdminService::createManual($base + ['phone' => $phone()], $partner)['booking'] === null);

// 9. Comprobantes
$tmpPng = tempnam(sys_get_temp_dir(), 'png');
$im = imagecreatetruecolor(40, 30);
imagepng($im, $tmpPng);
imagedestroy($im);
$err = BookingAdminService::storeFile($p['code'], ['name' => 'captura.png', 'tmp_name' => $tmpPng, 'size' => filesize($tmpPng), 'error' => UPLOAD_ERR_OK], $owner);
$f = Db::one('SELECT * FROM booking_files WHERE booking_id = ? ORDER BY id DESC LIMIT 1', [(int) $p['id']]);
$check('subir PNG lo recodifica y lo guarda fuera de public', $err === null && $f !== null && is_file(BASE_PATH . '/storage/uploads/comprobantes/' . $f['stored_name']) && $f['mime'] === 'image/png', (string) $err);
$tmpBad = tempnam(sys_get_temp_dir(), 'bad');
file_put_contents($tmpBad, "<?php echo 'x'; ?>");
$check('un archivo PHP con nombre .jpg se rechaza', BookingAdminService::storeFile($p['code'], ['name' => 'foto.jpg', 'tmp_name' => $tmpBad, 'size' => filesize($tmpBad), 'error' => UPLOAD_ERR_OK], $owner) !== null);
file_put_contents($tmpBad, str_repeat('a', 10));
$check('archivo de más de 5 MB se rechaza', BookingAdminService::storeFile($p['code'], ['name' => 'x.pdf', 'tmp_name' => $tmpBad, 'size' => 6 * 1024 * 1024, 'error' => UPLOAD_ERR_OK], $owner) !== null);
$check('el socio no puede subir comprobantes', BookingAdminService::storeFile($p['code'], ['name' => 'c.png', 'tmp_name' => $tmpPng, 'size' => filesize($tmpPng), 'error' => UPLOAD_ERR_OK], $partner) !== null);
@unlink($tmpPng);
@unlink($tmpBad);

// 10. Listado y búsqueda
$l = BookingAdminService::list('', $p['code']);
$check('buscar por código encuentra la reserva', $l['total'] >= 1 && $l['rows'][0]['code'] === $p['code']);
$l = BookingAdminService::list('canceladas', '');
$check('filtro de canceladas solo trae canceladas', array_reduce($l['rows'], static fn($c, $r) => $c && in_array($r['status'], ['cancelada', 'no_asistio', 'vencida'], true), true));

// 11. Cadena de hashes intacta
$v = EventLog::verify();
$check('la cadena de hashes del historial sigue intacta', $v['ok'], (string) $v['reason']);

echo $fails === 0 ? "\nTodo OK\n" : "\n{$fails} fallo(s)\n";
exit($fails === 0 ? 0 : 1);
