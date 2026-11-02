<?php
declare(strict_types=1);

namespace App\Controllers\Panel;

use App\Controllers\Controller;
use App\Domain\Orders\GiftCodeService;
use App\Http\Request;
use App\Http\Response;
use App\Http\Session;
use App\Panel\Auth;
use App\Support\Db;
use App\Support\Settings;

/** Pedidos en línea (cursos, bonos y productos pagados con Wompi) y canje de bonos. */
final class VentasController extends Controller
{
    public const STATUS = ['pendiente' => 'Pendiente', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada', 'anulada' => 'Anulada', 'reembolsada' => 'Reembolsada'];
    public const CHIP = ['pendiente' => 'sol', 'aprobada' => 'con', 'rechazada' => 'can', 'anulada' => 'ven', 'reembolsada' => 'can'];

    public function index(Request $request, array $redeem = []): Response
    {
        $status = $request->str('estado', 20);
        if (!array_key_exists($status, self::STATUS)) {
            $status = '';
        }
        $month = $request->str('mes', 7);
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $month = '';
        }
        $where = ['1 = 1'];
        $params = [];
        if ($status !== '') {
            $where[] = 'o.status = ?';
            $params[] = $status;
        }
        if ($month !== '') {
            $start = new \DateTimeImmutable($month . '-01', new \DateTimeZone((string) config('app.timezone')));
            $where[] = 'o.created_at >= ? AND o.created_at < ?';
            $params[] = $start->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            $params[] = $start->modify('first day of next month')->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        }
        $rows = Db::all(
            'SELECT o.id, o.reference, o.status, o.total, o.wompi_transaction_id, o.paid_at, o.created_at, o.commission_rate_bp,
                    c.id AS client_id, c.name AS client_name,
                    (SELECT GROUP_CONCAT(CONCAT(oi.qty, " × ", p.name) SEPARATOR ", ") FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = o.id) AS items,
                    (SELECT COALESCE(SUM(cl.amount), 0) FROM commission_lines cl WHERE cl.order_id = o.id) AS commission
             FROM orders o JOIN clients c ON c.id = o.client_id
             WHERE ' . implode(' AND ', $where) . ' ORDER BY o.created_at DESC LIMIT 200',
            $params,
        );
        $sum = ['count' => 0, 'paid' => 0, 'commission' => 0];
        foreach ($rows as $r) {
            $sum['count']++;
            if ($r['status'] === 'aprobada') {
                $sum['paid'] += (int) $r['total'];
            }
            $sum['commission'] += (int) $r['commission'];
        }
        $gifts = Db::all(
            'SELECT g.code, g.service_code, g.value, g.recipient, g.status, g.redeemed_at, g.created_at, b.code AS booking_code
             FROM gift_codes g LEFT JOIN bookings b ON b.id = g.redeemed_booking_id ORDER BY g.created_at DESC LIMIT 30',
        );
        return $this->view('panel/ventas', [
            'title' => 'Ventas en línea', 'active' => 'ventas', 'rows' => $rows, 'status' => $status, 'month' => $month, 'sum' => $sum,
            'gifts' => $gifts, 'canRedeem' => Auth::is('owner'), 'slots' => Settings::slots(),
            'redeem' => $redeem + ['errors' => [], 'old' => [], 'error' => null],
        ], 'panel', $redeem !== [] ? 422 : 200);
    }

    public function redeem(Request $request): Response
    {
        $old = [];
        foreach (['code' => 20, 'name' => 120, 'phone' => 30, 'email' => 190, 'date' => 10, 'time' => 5, 'location' => 6, 'address' => 255, 'notes' => 500] as $f => $len) {
            $old[$f] = $request->str($f, $len);
        }
        $res = GiftCodeService::redeem($old, (int) Auth::id(), $request->ip(), $request->userAgent());
        if ($res['error'] !== null) {
            return $this->index($request, ['errors' => $res['errors'], 'old' => $old, 'error' => $res['error']]);
        }
        if ($res['booking'] !== null) {
            Session::flash('notice', 'Bono ' . $res['gift']['code'] . ' canjeado. Reserva ' . $res['booking']['code'] . ' confirmada (no genera comisión adicional).');
            return Response::redirect('/panel/reservas/' . $res['booking']['code'], 303);
        }
        Session::flash('notice', 'Bono ' . $res['gift']['code'] . ' canjeado. Coordina la inscripción al curso con la alumna.');
        return Response::redirect('/panel/ventas', 303);
    }
}
