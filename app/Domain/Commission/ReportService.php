<?php
declare(strict_types=1);

namespace App\Domain\Commission;

use App\Domain\Attribution\Source;
use App\Domain\Booking\Availability;
use App\Support\Db;
use App\Support\Metrics;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Consultas del Resumen y de Comisiones. Toda la lógica de agregación vive aquí,
 * fuera de controladores y vistas.
 */
final class ReportService
{
    /**
     * Rango de un mes local (America/Bogota) en UTC.
     * @return array{start:DateTimeImmutable,end:DateTimeImmutable,from:string,to:string,fromDay:string,toDay:string,year:int,month:int}
     */
    public static function monthRange(int $year, int $month): array
    {
        $start = new DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month), Availability::tz());
        $end = $start->modify('first day of next month');
        $utc = new DateTimeZone('UTC');
        return [
            'start' => $start, 'end' => $end,
            'from' => $start->setTimezone($utc)->format('Y-m-d H:i:s'),
            'to' => $end->setTimezone($utc)->format('Y-m-d H:i:s'),
            'fromDay' => $start->format('Y-m-d'),
            'toDay' => $end->modify('-1 day')->format('Y-m-d'),
            'year' => $year, 'month' => $month,
        ];
    }

    /** Mes pedido con ?mes=AAAA-MM o el actual. @return array{0:int,1:int} */
    public static function parseMonth(string $raw): array
    {
        $now = local_time(now_utc());
        if (preg_match('/^(\d{4})-(\d{2})$/', $raw, $m) && (int) $m[2] >= 1 && (int) $m[2] <= 12 && (int) $m[1] >= 2024 && (int) $m[1] <= 2100) {
            return [(int) $m[1], (int) $m[2]];
        }
        return [(int) $now->format('Y'), (int) $now->format('n')];
    }

    /** @return array<string,mixed> */
    public static function dashboard(int $year, int $month): array
    {
        $r = self::monthRange($year, $month);
        $prev = $r['start']->modify('-1 month');
        $pr = self::monthRange((int) $prev->format('Y'), (int) $prev->format('n'));

        $requests = self::count('is_web_attributed = 1 AND created_at >= ? AND created_at < ?', [$r['from'], $r['to']]);
        $requestsPrev = self::count('is_web_attributed = 1 AND created_at >= ? AND created_at < ?', [$pr['from'], $pr['to']]);
        $confirmed = self::count('is_web_attributed = 1 AND created_at >= ? AND created_at < ? AND deposit_confirmed_at IS NOT NULL', [$r['from'], $r['to']]);
        $done = self::count("is_web_attributed = 1 AND status = 'realizada' AND starts_at >= ? AND starts_at < ?", [$r['from'], $r['to']]);
        $pending = self::count("is_web_attributed = 1 AND status IN ('confirmada','reprogramada') AND starts_at >= ? AND starts_at < ?", [$r['from'], $r['to']]);

        $period = CommissionService::period($year, $month);
        $commission = $period ? (int) $period['total_commission'] : 0;
        // Estimado: comisión ya generada + proyectada de las confirmadas pendientes del mes
        $projected = (int) Db::value(
            "SELECT COALESCE(SUM(FLOOR(total * commission_rate_bp / 10000)), 0) FROM bookings
             WHERE is_web_attributed = 1 AND gift_code_id IS NULL AND status IN ('confirmada','reprogramada') AND starts_at >= ? AND starts_at < ?",
            [$r['from'], $r['to']],
        );

        $m = [];
        try {
            $m = Metrics::totals($r['fromDay'], $r['toDay']);
        } catch (\Throwable) {
            $m = array_fill_keys(Metrics::ALLOWED, 0);
        }
        $funnel = [
            ['Visitas', (int) ($m['visits'] ?? 0)],
            ['Clics a WhatsApp o reserva', (int) ($m['whatsapp_clicks'] ?? 0) + (int) ($m['booking_starts'] ?? 0)],
            ['Solicitudes registradas', $requests],
            ['Confirmadas con abono', $confirmed],
            ['Realizadas', $done],
        ];
        $max = max(1, ...array_column($funnel, 1));

        $origin = [];
        foreach ([Source::WEB_FORM, Source::WHATSAPP_WEB, Source::GOOGLE_BUSINESS, Source::WEB_RETURNING] as $s) {
            $origin[] = [
                'label' => self::originLabel($s->value),
                'n' => self::count('is_web_attributed = 1 AND source = ? AND created_at >= ? AND created_at < ?', [$s->value, $r['from'], $r['to']]),
            ];
        }

        $upcoming = Db::all(
            "SELECT b.code, b.starts_at, b.status, b.people, s.code AS service_code, b.event_type, c.name AS client_name
             FROM bookings b JOIN services s ON s.id = b.service_id JOIN clients c ON c.id = b.client_id
             WHERE b.starts_at >= ? AND " . Availability::ACTIVE_SQL . ' ORDER BY b.starts_at ASC LIMIT 6',
            [now_utc(), now_utc()],
        );

        return [
            'range' => $r,
            'requests' => $requests,
            'requestsDelta' => $requests - $requestsPrev,
            'prevMonthName' => mes_nombre((int) $prev->format('n')),
            'confirmed' => $confirmed,
            'confirmedPct' => $requests > 0 ? (int) round($confirmed * 100 / $requests) : 0,
            'done' => $done,
            'pending' => $pending,
            'commission' => $commission,
            'commissionProjected' => $commission + $projected,
            'funnel' => array_map(static fn(array $f): array => ['label' => $f[0], 'n' => $f[1], 'w' => (int) round($f[1] * 100 / $max)], $funnel),
            'origin' => $origin,
            'upcoming' => $upcoming,
        ];
    }

    private static function count(string $where, array $params): int
    {
        return (int) Db::value('SELECT COUNT(*) FROM bookings WHERE ' . $where, $params);
    }

    public static function originLabel(string $source): string
    {
        return match ($source) {
            'web_form' => 'Formulario de reserva',
            'whatsapp_web' => 'WhatsApp con código WEB',
            'google_business' => 'Ficha de Google',
            'web_returning' => 'Clienta web que volvió (registro manual)',
            default => Source::tryFrom($source)?->label() ?? $source,
        };
    }

    /** Nombre corto: «Laura M.» */
    public static function shortName(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        if (count($parts) < 2) {
            return $name;
        }
        return $parts[0] . ' ' . mb_substr($parts[1], 0, 1) . '.';
    }

    /** «Pack 3 · Novia», «Pack 1 ×3» */
    public static function serviceShort(string $code, int $people = 1, ?string $event = null): string
    {
        $map = ['pack1' => 'Pack 1', 'pack2' => 'Pack 2', 'pack3' => 'Pack 3', 'curso-auto' => 'Curso auto', 'curso-pro' => 'Curso pro'];
        $s = $map[$code] ?? $code;
        if ($code === 'pack3' && $event !== null) {
            $s .= match ($event) {
                'boda' => ' · Novia',
                'quince' => ' · Quince',
                default => '',
            };
        }
        return $people > 1 ? $s . ' ×' . $people : $s;
    }

    // ------------------------------------------------------------------ Comisiones

    /** @return list<array<string,mixed>> */
    public static function periods(): array
    {
        return Db::all('SELECT * FROM commission_periods ORDER BY year DESC, month DESC');
    }

    /** Periodo por defecto: el más reciente en aprobación, si no el abierto, si no el más reciente. */
    public static function defaultPeriod(): ?array
    {
        return Db::one("SELECT * FROM commission_periods WHERE status = 'en_aprobacion' ORDER BY year DESC, month DESC LIMIT 1")
            ?? Db::one("SELECT * FROM commission_periods WHERE status = 'abierto' ORDER BY year DESC, month DESC LIMIT 1")
            ?? Db::one('SELECT * FROM commission_periods ORDER BY year DESC, month DESC LIMIT 1');
    }

    /** @return array<string,mixed> */
    public static function periodDetail(array $p): array
    {
        $id = (int) $p['id'];
        $services = Db::all(
            "SELECT l.*, b.code, b.people, b.event_type, b.starts_at, b.source, s.code AS service_code
             FROM commission_lines l JOIN bookings b ON b.id = l.booking_id JOIN services s ON s.id = b.service_id
             WHERE l.period_id = ? AND l.booking_id IS NOT NULL ORDER BY b.starts_at, l.id",
            [$id],
        );
        $orders = Db::all(
            "SELECT l.*, o.reference, o.paid_at, o.status AS order_status, o.wompi_transaction_id,
                    (SELECT GROUP_CONCAT(p.name SEPARATOR ', ') FROM order_items i JOIN products p ON p.id = i.product_id WHERE i.order_id = o.id) AS products
             FROM commission_lines l JOIN orders o ON o.id = l.order_id
             WHERE l.period_id = ? AND l.order_id IS NOT NULL ORDER BY l.created_at, l.id",
            [$id],
        );
        $sum = static fn(array $rows, string $k): int => array_sum(array_map(static fn(array $r): int => (int) $r[$k], $rows));

        $r = self::monthRange((int) $p['year'], (int) $p['month']);
        $proj = Db::one(
            "SELECT COALESCE(SUM(total),0) AS base, COUNT(*) AS n, COALESCE(SUM(FLOOR(total * commission_rate_bp / 10000)),0) AS amount FROM bookings
             WHERE is_web_attributed = 1 AND gift_code_id IS NULL AND status IN ('confirmada','reprogramada') AND starts_at >= ? AND starts_at < ?",
            [$r['from'], $r['to']],
        );
        $comments = Db::all(
            'SELECT c.*, u.name, u.role FROM commission_comments c JOIN users u ON u.id = c.user_id WHERE c.period_id = ? ORDER BY c.created_at',
            [$id],
        );
        $approvers = [];
        foreach (['owner' => 'owner_approved_at', 'partner' => 'partner_approved_at'] as $role => $col) {
            $u = Db::one('SELECT name FROM users WHERE role = ? AND active = 1 ORDER BY id LIMIT 1', [$role]);
            $approvers[$role] = ['name' => $u['name'] ?? ($role === 'owner' ? 'Dueña' : 'Socio'), 'at' => $p[$col]];
        }
        return [
            'services' => $services,
            'orders' => $orders,
            'servicesBase' => $sum($services, 'base'),
            'servicesAmount' => $sum($services, 'amount'),
            'servicesCount' => count($services),
            'ordersBase' => $sum($orders, 'base'),
            'ordersAmount' => $sum($orders, 'amount'),
            'ordersCount' => count($orders),
            'projectedBase' => (int) $proj['base'],
            'projectedCount' => (int) $proj['n'],
            'projectedAmount' => (int) $proj['amount'],
            'comments' => $comments,
            'approvers' => $approvers,
            'snapshotOk' => in_array($p['status'], ['cerrado', 'pagado'], true) ? CommissionService::verifySnapshot($id) : null,
        ];
    }

    /** Propuestas de tasa sin la doble aprobación. @return list<array<string,mixed>> */
    public static function pendingRates(): array
    {
        return Db::all(
            'SELECT r.*, u.name AS proposer FROM commission_rates r LEFT JOIN users u ON u.id = r.proposed_by
             WHERE r.approved_by_owner_at IS NULL OR r.approved_by_partner_at IS NULL ORDER BY r.created_at DESC LIMIT 20',
        );
    }

    /** Tasas aprobadas que entran en vigor en el futuro. @return list<array<string,mixed>> */
    public static function scheduledRates(): array
    {
        return Db::all(
            'SELECT * FROM commission_rates WHERE approved_by_owner_at IS NOT NULL AND approved_by_partner_at IS NOT NULL AND valid_from > ? ORDER BY valid_from',
            [now_utc()],
        );
    }

    public static function periodName(array $p): string
    {
        return mes_nombre((int) $p['month']) . ' ' . $p['year'];
    }
}
