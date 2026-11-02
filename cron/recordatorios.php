<?php
declare(strict_types=1);

/**
 * Cada 15 min (§16): recordatorio 24 h antes de las citas confirmadas y vencimiento de las
 * reservas solicitadas cuya retención de 48 h pasó sin abono (cupo liberado + correo).
 *   php cron/recordatorios.php
 */

require __DIR__ . '/_lock.php';

use App\Domain\Audit\EventLog;
use App\Domain\Booking\BookingService;
use App\Domain\Notifications\BookingNotifier;
use App\Support\Db;

/** @return int recordatorios enviados */
function cron_send_reminders(): int
{
    $now = now_utc();
    $until = gmdate('Y-m-d H:i:s', time() + 24 * 3600);
    $sent = 0;
    $rows = Db::all(
        "SELECT id FROM bookings WHERE status IN ('confirmada','reprogramada') AND reminder_sent_at IS NULL AND starts_at > ? AND starts_at <= ?",
        [$now, $until],
    );
    foreach ($rows as $r) {
        $b = BookingService::find((int) $r['id']);
        if ($b === null) {
            continue;
        }
        $ok = BookingNotifier::reminder($b);
        if ($ok || empty($b['client_email'])) {
            Db::run('UPDATE bookings SET reminder_sent_at = ? WHERE id = ? AND reminder_sent_at IS NULL', [now_utc(), (int) $b['id']]);
            $sent += $ok ? 1 : 0;
        }
    }
    return $sent;
}

/** @return int reservas vencidas */
function cron_expire_holds(): int
{
    $expired = 0;
    $ids = Db::all("SELECT id FROM bookings WHERE status = 'solicitada' AND hold_expires_at IS NOT NULL AND hold_expires_at < ?", [now_utc()]);
    foreach ($ids as $r) {
        $done = Db::transaction(static function () use ($r): bool {
            $b = Db::one('SELECT id, status FROM bookings WHERE id = ? FOR UPDATE', [(int) $r['id']]);
            if ($b === null || $b['status'] !== 'solicitada') {
                return false;
            }
            Db::run("UPDATE bookings SET status = 'vencida', updated_at = ? WHERE id = ?", [now_utc(), (int) $b['id']]);
            EventLog::record((int) $b['id'], 'vencida', 'solicitada', 'vencida', 'Sin abono en ' . (int) config('app.booking.hold_hours', 48) . ' h: cupo liberado');
            return true;
        });
        if ($done) {
            $expired++;
        }
    }
    // Aviso a la clienta (idempotente por expiry_notice_sent_at y por la clave del correo)
    foreach (Db::all("SELECT id FROM bookings WHERE status = 'vencida' AND expiry_notice_sent_at IS NULL AND updated_at >= ?", [gmdate('Y-m-d H:i:s', time() - 7 * 86400)]) as $r) {
        $b = BookingService::find((int) $r['id']);
        if ($b === null) {
            continue;
        }
        $ok = BookingNotifier::holdExpired($b);
        if ($ok || empty($b['client_email'])) {
            Db::run('UPDATE bookings SET expiry_notice_sent_at = ? WHERE id = ?', [now_utc(), (int) $b['id']]);
        }
    }
    return $expired;
}

if (cron_should_run()) {
    $lock = cron_lock('recordatorios');
    if ($lock !== null) {
        $e = cron_expire_holds();
        $s = cron_send_reminders();
        cron_out('recordatorios', "vencidas={$e} recordatorios={$s}");
    }
}
