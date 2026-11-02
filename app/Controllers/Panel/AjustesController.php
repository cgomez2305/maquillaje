<?php
declare(strict_types=1);

namespace App\Controllers\Panel;

use App\Controllers\Controller;
use App\Domain\Audit\AuditLog;
use App\Domain\Booking\Availability;
use App\Domain\Booking\Holidays;
use App\Domain\Catalog;
use App\Http\Request;
use App\Http\Response;
use App\Http\Session;
use App\Panel\Auth;
use App\Support\Db;
use App\Support\Settings;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Ajustes (solo dueña): horarios, días cerrados, festivos que se trabajan, bloqueos de agenda,
 * precios de servicios y de la tienda. Las tasas de comisión se cambian en Comisiones con doble aprobación.
 */
final class AjustesController extends Controller
{
    public const WEEKDAYS = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

    public function index(Request $request): Response
    {
        $errors = [];
        if ($request->isPost()) {
            $section = $request->str('section', 20);
            $errors = match ($section) {
                'slots' => $this->saveSlots($request),
                'weekdays' => $this->saveWeekdays($request),
                'holidays' => $this->saveHolidays($request),
                'block_add' => $this->addBlock($request),
                'block_del' => $this->deleteBlock($request),
                'services' => $this->saveServices($request),
                'products' => $this->saveProducts($request),
                default => ['form' => 'Acción no válida.'],
            };
            Settings::flush();
            Catalog::reset();
            if ($errors === []) {
                Session::flash('notice', 'Ajustes guardados.');
                return Response::redirect('/panel/ajustes#' . $section, 303);
            }
        }
        return $this->render($errors, $request);
    }

    private function render(array $errors, Request $request): Response
    {
        $tz = Availability::tz();
        $today = new DateTimeImmutable('today', $tz);
        $holidays = [];
        foreach ([(int) $today->format('Y'), (int) $today->format('Y') + 1] as $y) {
            foreach (Holidays::forYear($y) as $d => $name) {
                if ($d >= $today->format('Y-m-d') && $d <= $today->modify('+12 months')->format('Y-m-d')) {
                    $holidays[$d] = $name;
                }
            }
        }
        $blocks = Db::all('SELECT id, starts_at, ends_at, reason FROM blocked_slots WHERE ends_at >= ? ORDER BY starts_at LIMIT 100', [now_utc()]);
        return $this->view('panel/ajustes', [
            'title' => 'Ajustes', 'active' => 'ajustes', 'errors' => $errors, 'old' => $request->isPost() ? $request->post : [],
            'slots' => Settings::slots(),
            'closed' => array_map('intval', (array) Settings::get('closed_weekdays', [])),
            'openHolidays' => (array) Settings::get('open_holidays', []),
            'holidays' => $holidays,
            'blocks' => $blocks,
            'services' => Db::all('SELECT id, code, name, price, category, active FROM services ORDER BY category DESC, id'),
            'products' => Db::all('SELECT id, slug, name, category, subcategory, price, active FROM products ORDER BY sort, id'),
        ], 'panel', $errors ? 422 : 200);
    }

    /** @return array<string,string> */
    private function saveSlots(Request $request): array
    {
        $raw = $request->str('slots', 300);
        $slots = [];
        foreach (preg_split('/[\s,;]+/', $raw) ?: [] as $s) {
            if ($s === '') {
                continue;
            }
            if (preg_match('/^(\d{1,2}):([0-5]\d)$/', $s, $m) && (int) $m[1] <= 23) {
                $slots[] = sprintf('%02d:%s', (int) $m[1], $m[2]);
            } else {
                return ['slots' => "«{$s}» no es una hora válida. Usa el formato 24 h, por ejemplo 07:00, 14:30."];
            }
        }
        $slots = array_values(array_unique($slots));
        sort($slots);
        if ($slots === [] || count($slots) > 24) {
            return ['slots' => 'Escribe entre 1 y 24 horas de inicio.'];
        }
        $before = Settings::slots();
        Settings::set('booking_slots', $slots, Auth::id());
        AuditLog::record(Auth::id(), 'ajuste_horarios', 'booking_slots', ['antes' => $before, 'despues' => $slots], $request->ip());
        return [];
    }

    private function saveWeekdays(Request $request): array
    {
        $days = array_values(array_filter(array_map('intval', (array) ($request->post['closed'] ?? [])), static fn(int $d): bool => $d >= 1 && $d <= 7));
        sort($days);
        if (count($days) === 7) {
            return ['weekdays' => 'No puedes cerrar todos los días de la semana.'];
        }
        $before = (array) Settings::get('closed_weekdays', []);
        Settings::set('closed_weekdays', array_values(array_unique($days)), Auth::id());
        AuditLog::record(Auth::id(), 'ajuste_dias_cerrados', 'closed_weekdays', ['antes' => $before, 'despues' => $days], $request->ip());
        return [];
    }

    private function saveHolidays(Request $request): array
    {
        $open = [];
        foreach ((array) ($request->post['open'] ?? []) as $d) {
            if (is_string($d) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && Holidays::name($d) !== null) {
                $open[] = $d;
            }
        }
        // Conserva festivos pasados ya abiertos solo si siguen siendo futuros
        sort($open);
        $before = (array) Settings::get('open_holidays', []);
        Settings::set('open_holidays', array_values(array_unique($open)), Auth::id());
        AuditLog::record(Auth::id(), 'ajuste_festivos', 'open_holidays', ['antes' => $before, 'despues' => $open], $request->ip());
        return [];
    }

    private function addBlock(Request $request): array
    {
        $tz = Availability::tz();
        $date = $request->str('date', 10);
        $dateTo = $request->str('date_to', 10) ?: $date;
        $allDay = $request->input('all_day') === '1';
        $from = $allDay ? '00:00' : $request->str('from', 5);
        $to = $allDay ? '23:59' : $request->str('to', 5);
        $reason = $request->str('reason', 120);
        $d1 = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $from, $tz);
        $d2 = DateTimeImmutable::createFromFormat('!Y-m-d H:i', $dateTo . ' ' . $to, $tz);
        if ($d1 === false || $d2 === false || $d1->format('Y-m-d H:i') !== $date . ' ' . $from) {
            return ['block' => 'Revisa la fecha y las horas del bloqueo.'];
        }
        if ($allDay) {
            $d2 = $d2->setTime(23, 59, 59);
        }
        if ($d2 <= $d1) {
            return ['block' => 'La hora final debe ser posterior a la inicial.'];
        }
        if ($d1->diff($d2)->days > 90) {
            return ['block' => 'Un bloqueo puede durar como máximo 90 días.'];
        }
        $utc = new DateTimeZone('UTC');
        $s = $d1->setTimezone($utc)->format('Y-m-d H:i:s');
        $e = $d2->setTimezone($utc)->format('Y-m-d H:i:s');
        $id = Db::insert('INSERT INTO blocked_slots (starts_at, ends_at, reason, created_by, created_at) VALUES (?, ?, ?, ?, ?)', [$s, $e, $reason !== '' ? $reason : null, Auth::id(), now_utc()]);
        $clash = (int) Db::value(
            "SELECT COUNT(*) FROM bookings WHERE starts_at < ? AND ends_at > ? AND status IN ('solicitada','confirmada','reprogramada')",
            [$e, $s],
        );
        AuditLog::record(Auth::id(), 'bloqueo_creado', 'blocked_slot:' . $id, ['inicio' => $s, 'fin' => $e, 'motivo' => $reason], $request->ip());
        if ($clash > 0) {
            Session::flash('error', "Bloqueo creado. Atención: hay {$clash} reserva(s) activas en ese rango; revísalas en el calendario.");
        }
        return [];
    }

    private function deleteBlock(Request $request): array
    {
        $id = $request->int('id');
        $row = Db::one('SELECT * FROM blocked_slots WHERE id = ?', [$id]);
        if ($row === null) {
            return ['block' => 'El bloqueo no existe.'];
        }
        Db::run('DELETE FROM blocked_slots WHERE id = ?', [$id]);
        AuditLog::record(Auth::id(), 'bloqueo_eliminado', 'blocked_slot:' . $id, ['inicio' => $row['starts_at'], 'fin' => $row['ends_at'], 'motivo' => $row['reason']], $request->ip());
        return [];
    }

    private function saveServices(Request $request): array
    {
        $prices = (array) ($request->post['price'] ?? []);
        $active = (array) ($request->post['active'] ?? []);
        $changes = [];
        foreach (Db::all('SELECT id, code, price, active FROM services') as $s) {
            $raw = $prices[$s['id']] ?? null;
            if ($raw === null) {
                continue;
            }
            $p = (int) preg_replace('/\D/', '', (string) $raw);
            if ($p < 1000 || $p > 50000000) {
                return ['services' => 'Revisa el precio de ' . $s['code'] . ' (entre 1.000 y 50.000.000 COP).'];
            }
            $a = isset($active[$s['id']]) ? 1 : 0;
            if ($p !== (int) $s['price'] || $a !== (int) $s['active']) {
                Db::run('UPDATE services SET price = ?, active = ? WHERE id = ?', [$p, $a, (int) $s['id']]);
                $changes[$s['code']] = ['antes' => [(int) $s['price'], (int) $s['active']], 'despues' => [$p, $a]];
            }
        }
        if ($changes) {
            AuditLog::record(Auth::id(), 'precios_servicios', 'services', $changes, $request->ip());
        }
        return [];
    }

    private function saveProducts(Request $request): array
    {
        $prices = (array) ($request->post['price'] ?? []);
        $active = (array) ($request->post['active'] ?? []);
        $changes = [];
        foreach (Db::all('SELECT id, slug, price, active FROM products') as $pr) {
            $raw = $prices[$pr['id']] ?? null;
            if ($raw === null) {
                continue;
            }
            $p = (int) preg_replace('/\D/', '', (string) $raw);
            $a = isset($active[$pr['id']]) ? 1 : 0;
            if ($p > 50000000) {
                return ['products' => 'Revisa el precio de ' . $pr['slug'] . '.'];
            }
            if ($a === 1 && $p < 1000) {
                return ['products' => 'Para activar «' . $pr['slug'] . '» en la tienda primero ponle precio.'];
            }
            if ($p !== (int) $pr['price'] || $a !== (int) $pr['active']) {
                Db::run('UPDATE products SET price = ?, active = ? WHERE id = ?', [$p, $a, (int) $pr['id']]);
                $changes[$pr['slug']] = ['antes' => [(int) $pr['price'], (int) $pr['active']], 'despues' => [$p, $a]];
            }
        }
        if ($changes) {
            AuditLog::record(Auth::id(), 'precios_tienda', 'products', $changes, $request->ip());
        }
        return [];
    }
}
