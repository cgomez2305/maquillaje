<?php
declare(strict_types=1);

namespace App\Controllers\Panel;

use App\Controllers\Controller;
use App\Domain\Audit\AuditLog;
use App\Domain\Audit\EventLog;
use App\Domain\Commission\CommissionService;
use App\Domain\Commission\ReportService;
use App\Http\Request;
use App\Http\Response;
use App\Panel\Auth;
use App\Support\Db;

/** /panel/historial: eventos de reservas, registro del panel y verificación de integridad (§9.6). */
final class HistorialController extends Controller
{
    private const PER_PAGE = 40;

    public function index(Request $request): Response
    {
        $tab = $request->str('vista', 10) === 'panel' ? 'panel' : 'reservas';
        $type = preg_replace('/[^a-z_]/', '', $request->str('tipo', 40)) ?? '';
        $from = $this->date($request->str('desde', 10));
        $to = $this->date($request->str('hasta', 10));
        $page = max(1, $request->int('p', 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $where = [];
        $params = [];
        $tz = new \DateTimeZone((string) config('app.timezone'));
        $utc = new \DateTimeZone('UTC');
        $col = $tab === 'panel' ? 'a.created_at' : 'e.created_at';
        if ($from !== null) {
            $where[] = "{$col} >= ?";
            $params[] = (new \DateTimeImmutable($from . ' 00:00:00', $tz))->setTimezone($utc)->format('Y-m-d H:i:s');
        }
        if ($to !== null) {
            $where[] = "{$col} < ?";
            $params[] = (new \DateTimeImmutable($to . ' 00:00:00', $tz))->modify('+1 day')->setTimezone($utc)->format('Y-m-d H:i:s');
        }

        if ($tab === 'panel') {
            $types = array_column(Db::all('SELECT DISTINCT action FROM audit_log ORDER BY action'), 'action');
            if ($type !== '' && in_array($type, $types, true)) {
                $where[] = 'a.action = ?';
                $params[] = $type;
            }
            $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            $rows = Db::all(
                "SELECT a.*, u.name AS user_name FROM audit_log a LEFT JOIN users u ON u.id = a.user_id {$sqlWhere}
                 ORDER BY a.id DESC LIMIT " . (self::PER_PAGE + 1) . ' OFFSET ' . $offset,
                $params,
            );
        } else {
            $types = array_column(Db::all('SELECT DISTINCT type FROM booking_events ORDER BY type'), 'type');
            if ($type !== '' && in_array($type, $types, true)) {
                $where[] = 'e.type = ?';
                $params[] = $type;
            }
            $sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';
            $rows = Db::all(
                "SELECT e.id, e.type, e.from_status, e.to_status, e.reason, e.created_at, e.hash, b.code, u.name AS actor_name
                 FROM booking_events e JOIN bookings b ON b.id = e.booking_id LEFT JOIN users u ON u.id = e.actor_user_id {$sqlWhere}
                 ORDER BY e.id DESC LIMIT " . (self::PER_PAGE + 1) . ' OFFSET ' . $offset,
                $params,
            );
        }
        $hasMore = count($rows) > self::PER_PAGE;
        $rows = array_slice($rows, 0, self::PER_PAGE);

        return $this->view('panel/historial', [
            'title' => 'Historial',
            'active' => 'historial',
            'tab' => $tab,
            'rows' => $rows,
            'types' => $types,
            'type' => $type,
            'from' => $from,
            'to' => $to,
            'page' => $page,
            'hasMore' => $hasMore,
            'verify' => null,
        ], 'panel');
    }

    public function verify(Request $request): Response
    {
        $chain = EventLog::verify();
        $periods = [];
        foreach (Db::all("SELECT * FROM commission_periods WHERE status IN ('cerrado','pagado') ORDER BY year DESC, month DESC") as $p) {
            $periods[] = ['name' => ReportService::periodName($p), 'ok' => CommissionService::verifySnapshot((int) $p['id'])];
        }
        AuditLog::record(Auth::id(), 'historial_verificado', null, ['ok' => $chain['ok'], 'eventos' => $chain['count']], $request->ip());
        $broken = null;
        if ($chain['broken_id'] !== null) {
            $broken = Db::one('SELECT e.id, e.type, e.created_at, b.code FROM booking_events e JOIN bookings b ON b.id = e.booking_id WHERE e.id = ?', [$chain['broken_id']]);
        }
        return $this->view('panel/historial', [
            'title' => 'Verificación del historial',
            'active' => 'historial',
            'tab' => 'verificar',
            'rows' => [], 'types' => [], 'type' => '', 'from' => null, 'to' => null, 'page' => 1, 'hasMore' => false,
            'verify' => ['chain' => $chain, 'broken' => $broken, 'periods' => $periods],
        ], 'panel');
    }

    private function date(string $v): ?string
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return null;
        }
        $d = \DateTimeImmutable::createFromFormat('!Y-m-d', $v);
        return $d && $d->format('Y-m-d') === $v ? $v : null;
    }
}
