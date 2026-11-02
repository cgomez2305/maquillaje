<?php
declare(strict_types=1);

/**
 * Cada hora (§9.6, §10): 24 h después de la cita crea la encuesta post-servicio y la envía.
 * Incluye realizadas y confirmadas aún sin marcar (si la clienta dice que sí asistió y la reserva
 * termina cancelada o «no asistió», salta la alerta). Ventana de 14 días para no escribir tarde.
 *   php cron/encuestas.php
 */

require __DIR__ . '/_lock.php';

use App\Domain\Booking\BookingService;
use App\Domain\Notifications\BookingNotifier;
use App\Domain\Surveys\SurveyService;
use App\Support\Db;

/** @return array{created:int,sent:int} */
function cron_send_surveys(): array
{
    $created = 0;
    $sent = 0;
    $rows = Db::all(
        "SELECT b.id FROM bookings b LEFT JOIN surveys s ON s.booking_id = b.id
         WHERE b.status IN ('realizada','confirmada','reprogramada') AND b.starts_at <= ? AND b.starts_at >= ? AND s.id IS NULL",
        [gmdate('Y-m-d H:i:s', time() - 24 * 3600), gmdate('Y-m-d H:i:s', time() - 14 * 86400)],
    );
    foreach ($rows as $r) {
        if (SurveyService::create((int) $r['id']) !== null) {
            $created++;
        }
    }
    // Envío (también reintenta las creadas cuyo correo falló)
    $pending = Db::all(
        'SELECT s.id, s.booking_id, s.token FROM surveys s JOIN bookings b ON b.id = s.booking_id
         WHERE s.sent_at IS NULL AND s.answered_at IS NULL AND b.starts_at >= ?',
        [gmdate('Y-m-d H:i:s', time() - 14 * 86400)],
    );
    foreach ($pending as $s) {
        $b = BookingService::find((int) $s['booking_id']);
        if ($b === null) {
            continue;
        }
        $ok = BookingNotifier::survey($b, (string) $s['token']);
        if ($ok || empty($b['client_email'])) {
            Db::run('UPDATE surveys SET sent_at = ? WHERE id = ? AND sent_at IS NULL', [now_utc(), (int) $s['id']]);
            $sent += $ok ? 1 : 0;
        }
    }
    return ['created' => $created, 'sent' => $sent];
}

if (cron_should_run()) {
    $lock = cron_lock('encuestas');
    if ($lock !== null) {
        $r = cron_send_surveys();
        cron_out('encuestas', "creadas={$r['created']} enviadas={$r['sent']}");
    }
}
