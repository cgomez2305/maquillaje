<?php
declare(strict_types=1);

/**
 * Cada hora (§9.6): alerta al socio por reservas confirmadas cuya fecha pasó hace más de 72 h
 * sin marcarse como realizadas (una sola vez por reserva).
 *   php cron/alertas.php
 * Lunes 8:00: resumen semanal al socio con las alertas de la semana.
 *   php cron/alertas.php --resumen
 */

require __DIR__ . '/_lock.php';

use App\Domain\Alerts\AlertService;
use App\Domain\Notifications\Mailer;
use App\Support\Db;

/** @return int alertas nuevas */
function cron_unmarked_alerts(): int
{
    $hours = (int) config('app.commission.alert_unmarked_after_hours', 72);
    $n = 0;
    $rows = Db::all(
        "SELECT b.id, b.code, b.client_id, b.starts_at, b.is_web_attributed FROM bookings b
         WHERE b.status IN ('confirmada','reprogramada') AND b.starts_at < ?",
        [gmdate('Y-m-d H:i:s', time() - $hours * 3600)],
    );
    foreach ($rows as $b) {
        $start = local_time($b['starts_at']);
        $days = (int) floor((time() - strtotime($b['starts_at'] . ' UTC')) / 86400);
        $msg = $b['code'] . ' sigue sin marcarse como realizada y su fecha (' . fecha_corta($start) . ') pasó hace ' . $days . ' días.'
            . ((int) $b['is_web_attributed'] ? ' Es una reserva atribuida a la web.' : '');
        if (AlertService::raise('sin_marcar', $msg, 'sin-marcar-' . $b['code'], (int) $b['id'], (int) $b['client_id'], ['dias' => $days])) {
            $n++;
        }
    }
    return $n;
}

/** @return int alertas incluidas en el resumen (-1 si no hay correo del socio) */
function cron_weekly_digest(): int
{
    $rows = Db::all(
        'SELECT a.*, b.code FROM alerts a LEFT JOIN bookings b ON b.id = a.booking_id
         WHERE a.digest_sent_at IS NULL AND a.created_at >= ? ORDER BY a.created_at',
        [gmdate('Y-m-d H:i:s', time() - 7 * 86400)],
    );
    $partner = Mailer::partner();
    if ($partner === '') {
        return -1;
    }
    $from = local_time(gmdate('Y-m-d H:i:s', time() - 7 * 86400));
    $to = local_time(now_utc());
    $key = 'resumen-' . $to->format('o-\SW');
    $ok = Mailer::send($partner, 'Resumen semanal de alertas · Panel Fletcher', 'alertas-resumen', [
        'alerts' => $rows, 'types' => AlertService::TYPES,
        'from' => fecha_corta($from), 'to' => fecha_corta($to), 'url' => url('/panel'),
    ], $key);
    if ($ok && $rows !== []) {
        $ids = array_map(static fn(array $r): int => (int) $r['id'], $rows);
        Db::run('UPDATE alerts SET digest_sent_at = ? WHERE id IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', [now_utc(), ...$ids]);
    }
    return count($rows);
}

if (cron_should_run()) {
    $resumen = in_array('--resumen', $argv ?? [], true);
    $lock = cron_lock($resumen ? 'alertas-resumen' : 'alertas');
    if ($lock !== null) {
        if ($resumen) {
            $n = cron_weekly_digest();
            cron_out('alertas', $n < 0 ? 'sin PARTNER_EMAIL: resumen no enviado' : "resumen semanal con {$n} alertas");
        } else {
            $n = cron_unmarked_alerts();
            cron_out('alertas', "sin_marcar nuevas={$n}");
        }
    }
}
