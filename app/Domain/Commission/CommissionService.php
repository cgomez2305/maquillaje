<?php
declare(strict_types=1);

namespace App\Domain\Commission;

use App\Support\Db;

/**
 * Comisiones (§9.4, §9.5).
 *
 * - Las líneas se generan en el momento en que el hecho ocurre (reserva realizada, no asistió con
 *   abono, pedido aprobado, reembolso) y son idempotentes por `ref`.
 * - La línea va al periodo del mes del servicio/pago si ese periodo sigue «abierto»; si ya está en
 *   aprobación, cerrado o pagado, entra al periodo abierto actual como ajuste (nunca reabre uno).
 * - El día 1 (cron) el mes anterior pasa a «en_aprobacion»; con las dos aprobaciones se «cierra»
 *   guardando el hash de las líneas; la dueña lo marca «pagado» con una nota.
 */
final class CommissionService
{
    public const STATUS_LABELS = ['abierto' => 'Corte abierto', 'en_aprobacion' => 'En aprobación', 'cerrado' => 'Cerrado', 'pagado' => 'Pagado'];

    /** Registra (o no) la línea de una reserva según su estado actual. Idempotente. */
    public static function recordBooking(int $bookingId): ?int
    {
        $b = Db::one('SELECT id, status, total, deposit_amount, deposit_confirmed_at, is_web_attributed, commission_rate_bp, gift_code_id, starts_at FROM bookings WHERE id = ?', [$bookingId]);
        if ($b === null || !(int) $b['is_web_attributed'] || $b['gift_code_id'] !== null) {
            return null; // fuera de la web o canje de bono: no comisiona (§11)
        }
        if ($b['status'] === 'realizada') {
            return self::addLine('B' . $b['id'] . '-servicio', 'servicio', (int) $b['total'], (int) $b['commission_rate_bp'], (string) $b['starts_at'], $bookingId, null);
        }
        if ($b['status'] === 'no_asistio' && $b['deposit_confirmed_at'] !== null && config('app.commission.no_show_commissions_deposit', true)) {
            return self::addLine('B' . $b['id'] . '-no_asistio', 'no_asistio', (int) $b['deposit_amount'], (int) $b['commission_rate_bp'], (string) $b['starts_at'], $bookingId, null, 'Abono retenido por no asistencia');
        }
        return null; // cancelada, vencida, etc.: no comisiona
    }

    /** Pedido aprobado por Wompi. */
    public static function recordOrder(int $orderId): ?int
    {
        $o = Db::one('SELECT id, total, status, commission_rate_bp, is_web_attributed, paid_at FROM orders WHERE id = ?', [$orderId]);
        if ($o === null || $o['status'] !== 'aprobada' || !(int) $o['is_web_attributed']) {
            return null;
        }
        return self::addLine('O' . $o['id'] . '-pedido', 'pedido', (int) $o['total'], (int) $o['commission_rate_bp'], (string) ($o['paid_at'] ?? now_utc()), null, $orderId);
    }

    /** Reembolso posterior: línea negativa en el periodo en que ocurre (§9.4). */
    public static function recordRefund(int $orderId): ?int
    {
        $o = Db::one('SELECT id, total, commission_rate_bp, is_web_attributed, refunded_at FROM orders WHERE id = ?', [$orderId]);
        if ($o === null || !(int) $o['is_web_attributed'] || !Db::value("SELECT 1 FROM commission_lines WHERE ref = ?", ['O' . $o['id'] . '-pedido'])) {
            return null;
        }
        return self::addLine('O' . $o['id'] . '-reembolso', 'reembolso', -(int) $o['total'], (int) $o['commission_rate_bp'], now_utc(), null, $orderId, 'Reembolso del pedido', true);
    }

    /**
     * @param string $eventUtc fecha del hecho (servicio o pago) en UTC
     */
    private static function addLine(string $ref, string $kind, int $base, int $rateBp, string $eventUtc, ?int $bookingId, ?int $orderId, ?string $note = null, bool $forceCurrent = false): ?int
    {
        return Db::transaction(static function () use ($ref, $kind, $base, $rateBp, $eventUtc, $bookingId, $orderId, $note, $forceCurrent): ?int {
            if (Db::value('SELECT id FROM commission_lines WHERE ref = ?', [$ref]) !== null) {
                return null;
            }
            $local = local_time($eventUtc) ?? new \DateTimeImmutable();
            $nowL = local_time(now_utc());
            $sameMonth = $local->format('Y-m') === $nowL->format('Y-m');
            // El periodo del mes en curso se crea si aún no existe; uno pasado solo sirve si sigue abierto
            $period = $forceCurrent ? null : ($sameMonth
                ? self::ensurePeriod((int) $local->format('Y'), (int) $local->format('n'))
                : self::period((int) $local->format('Y'), (int) $local->format('n')));
            if ($period === null || $period['status'] !== 'abierto') {
                $now = local_time(now_utc());
                $period = self::ensurePeriod((int) $now->format('Y'), (int) $now->format('n'));
                if ($period['status'] !== 'abierto') {
                    // Caso límite: el mes en curso ya no está abierto (no debería ocurrir); usa el siguiente
                    $next = $now->modify('first day of next month');
                    $period = self::ensurePeriod((int) $next->format('Y'), (int) $next->format('n'));
                }
                if (!$forceCurrent) {
                    $note = trim(($note ? $note . ' · ' : '') . 'Ajuste de ' . mes_nombre((int) $local->format('n')) . ' ' . $local->format('Y'));
                }
            }
            $amount = RateService::amount($base, $rateBp);
            $id = Db::insert(
                'INSERT INTO commission_lines (period_id, booking_id, order_id, kind, base, rate_bp, amount, note, ref, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [(int) $period['id'], $bookingId, $orderId, $kind, $base, $rateBp, $amount, $note, $ref, now_utc()],
            );
            self::refreshTotals((int) $period['id']);
            return $id;
        });
    }

    /** @return array<string,mixed>|null */
    public static function period(int $year, int $month): ?array
    {
        return Db::one('SELECT * FROM commission_periods WHERE year = ? AND month = ?', [$year, $month]);
    }

    /** @return array<string,mixed> */
    public static function ensurePeriod(int $year, int $month): array
    {
        Db::run("INSERT IGNORE INTO commission_periods (year, month, status) VALUES (?, ?, 'abierto')", [$year, $month]);
        return (array) self::period($year, $month);
    }

    public static function refreshTotals(int $periodId): void
    {
        $t = Db::one('SELECT COALESCE(SUM(base),0) AS b, COALESCE(SUM(amount),0) AS a FROM commission_lines WHERE period_id = ?', [$periodId]);
        Db::run("UPDATE commission_periods SET total_base = ?, total_commission = ? WHERE id = ? AND status IN ('abierto','en_aprobacion')", [(int) $t['b'], (int) $t['a'], $periodId]);
    }

    /** Cron día 1: pasa a «en_aprobacion» los periodos abiertos de meses anteriores. @return list<int> */
    public static function startApproval(?\DateTimeImmutable $nowLocal = null): array
    {
        $nowLocal ??= local_time(now_utc());
        $cur = (int) $nowLocal->format('Y') * 12 + (int) $nowLocal->format('n');
        $moved = [];
        foreach (Db::all("SELECT id, year, month FROM commission_periods WHERE status = 'abierto'") as $p) {
            if ((int) $p['year'] * 12 + (int) $p['month'] < $cur) {
                self::refreshTotals((int) $p['id']);
                Db::run("UPDATE commission_periods SET status = 'en_aprobacion' WHERE id = ?", [(int) $p['id']]);
                $moved[] = (int) $p['id'];
            }
        }
        $y = (int) $nowLocal->format('Y');
        $m = (int) $nowLocal->format('n');
        self::ensurePeriod($y, $m);
        // Asegura también el mes anterior aunque no haya tenido movimiento (corte en cero)
        $prev = $nowLocal->modify('first day of last month');
        $pp = self::period((int) $prev->format('Y'), (int) $prev->format('n'));
        if ($pp === null) {
            Db::run("INSERT INTO commission_periods (year, month, status) VALUES (?, ?, 'en_aprobacion')", [(int) $prev->format('Y'), (int) $prev->format('n')]);
            $moved[] = (int) Db::pdo()->lastInsertId();
        }
        return $moved;
    }

    /**
     * Aprobación de una de las partes. Con ambas, cierra el periodo y guarda el hash de las líneas.
     * @return string|null error
     */
    public static function approve(int $periodId, string $role): ?string
    {
        if (!in_array($role, ['owner', 'partner'], true)) {
            return 'Solo la dueña y el socio aprueban cortes.';
        }
        return Db::transaction(static function () use ($periodId, $role): ?string {
            $p = Db::one('SELECT * FROM commission_periods WHERE id = ? FOR UPDATE', [$periodId]);
            if ($p === null) {
                return 'El corte no existe.';
            }
            if ($p['status'] !== 'en_aprobacion') {
                return $p['status'] === 'abierto' ? 'El corte sigue abierto: se aprueba a partir del día 1 del mes siguiente.' : 'El corte ya está cerrado.';
            }
            $col = $role === 'owner' ? 'owner_approved_at' : 'partner_approved_at';
            if ($p[$col] === null) {
                Db::run("UPDATE commission_periods SET {$col} = ? WHERE id = ?", [now_utc(), $periodId]);
                $p[$col] = now_utc();
            }
            if ($p['owner_approved_at'] !== null && $p['partner_approved_at'] !== null) {
                self::refreshTotals($periodId);
                Db::run("UPDATE commission_periods SET status = 'cerrado', closed_at = ?, snapshot_hash = ? WHERE id = ?", [now_utc(), self::snapshotHash($periodId), $periodId]);
            }
            return null;
        });
    }

    public static function markPaid(int $periodId, string $note): ?string
    {
        $note = trim($note);
        if (mb_strlen($note) < 4) {
            return 'Escribe la fecha y el medio de pago.';
        }
        $p = Db::one('SELECT status FROM commission_periods WHERE id = ?', [$periodId]);
        if ($p === null || $p['status'] !== 'cerrado') {
            return 'Solo un corte cerrado se puede marcar como pagado.';
        }
        Db::run("UPDATE commission_periods SET status = 'pagado', paid_at = ?, payment_note = ? WHERE id = ?", [now_utc(), mb_substr($note, 0, 255), $periodId]);
        return null;
    }

    /** Hash SHA-256 de las líneas del periodo (id, referencias, base, tasa, valor). */
    public static function snapshotHash(int $periodId): string
    {
        $rows = Db::all('SELECT id, booking_id, order_id, kind, base, rate_bp, amount, ref FROM commission_lines WHERE period_id = ? ORDER BY id', [$periodId]);
        $parts = array_map(static fn(array $r): string => implode('|', [$r['id'], $r['booking_id'] ?? '', $r['order_id'] ?? '', $r['kind'], $r['base'], $r['rate_bp'], $r['amount'], $r['ref']]), $rows);
        return hash('sha256', $periodId . "\n" . implode("\n", $parts));
    }

    /** ¿Las líneas actuales coinciden con el hash guardado al cerrar? */
    public static function verifySnapshot(int $periodId): ?bool
    {
        $h = Db::value('SELECT snapshot_hash FROM commission_periods WHERE id = ?', [$periodId]);
        return $h === null ? null : hash_equals((string) $h, self::snapshotHash($periodId));
    }
}
