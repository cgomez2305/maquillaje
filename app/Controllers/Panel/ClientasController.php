<?php
declare(strict_types=1);

namespace App\Controllers\Panel;

use App\Controllers\Controller;
use App\Domain\Clients\AnonymizeService;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Session;
use App\Panel\Auth;
use App\Support\Db;

/** Clientas (dueña y socio; el socio ve teléfonos y correos enmascarados, §14.5). */
final class ClientasController extends Controller
{
    private const PER_PAGE = 50;

    public const SOURCES = [
        'web_form' => 'Formulario web', 'whatsapp_web' => 'WhatsApp desde la web', 'google_business' => 'Google Business',
        'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'referral' => 'Recomendación', 'manual_other' => 'Manual',
    ];

    public function index(Request $request): Response
    {
        $q = $request->str('q', 80);
        $page = max(1, $request->int('p', 1));
        $where = ['1 = 1'];
        $params = [];
        if ($q !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
            $parts = ['c.name LIKE ?'];
            $params[] = $like;
            // Buscar por teléfono o correo solo la dueña: el socio los ve enmascarados (§14.5)
            if (Auth::is('owner')) {
                $parts[] = 'c.email LIKE ?';
                $params[] = $like;
                $digits = preg_replace('/\D/', '', $q) ?? '';
                if (strlen($digits) >= 4) {
                    $parts[] = 'c.phone_e164 LIKE ?';
                    $params[] = '%' . $digits . '%';
                }
            }
            $where[] = '(' . implode(' OR ', $parts) . ')';
        }
        $whereSql = implode(' AND ', $where);
        $total = (int) Db::value("SELECT COUNT(*) FROM clients c WHERE {$whereSql}", $params);
        $offset = ($page - 1) * self::PER_PAGE;
        $rows = Db::all(
            "SELECT c.id, c.name, c.phone_e164, c.email, c.first_source, c.web_attributed_until, c.anonymized_at, c.created_at,
                    COUNT(b.id) AS bookings, COALESCE(SUM(CASE WHEN b.status = 'realizada' THEN b.total ELSE 0 END), 0) AS done_value
             FROM clients c LEFT JOIN bookings b ON b.client_id = c.id
             WHERE {$whereSql}
             GROUP BY c.id ORDER BY c.created_at DESC LIMIT " . self::PER_PAGE . " OFFSET {$offset}",
            $params,
        );
        return $this->view('panel/clientas', [
            'title' => 'Clientas', 'active' => 'clientas', 'rows' => $rows, 'q' => $q, 'page' => $page,
            'pages' => max(1, (int) ceil($total / self::PER_PAGE)), 'total' => $total,
            'activeWeb' => (int) Db::value('SELECT COUNT(*) FROM clients WHERE web_attributed_until >= ? AND anonymized_at IS NULL', [now_utc()]),
        ], 'panel');
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->param('id');
        $c = Db::one('SELECT * FROM clients WHERE id = ?', [$id]);
        if ($c === null) {
            throw new HttpException(404);
        }
        $bookings = Db::all(
            'SELECT b.code, b.status, b.starts_at, b.total, b.source, b.is_web_attributed, s.name AS service_name
             FROM bookings b JOIN services s ON s.id = b.service_id WHERE b.client_id = ? ORDER BY b.starts_at DESC',
            [$id],
        );
        $orders = Db::all('SELECT reference, status, total, paid_at, created_at FROM orders WHERE client_id = ? ORDER BY created_at DESC', [$id]);
        return $this->view('panel/clienta', [
            'title' => $c['name'], 'active' => 'clientas', 'c' => $c, 'bookings' => $bookings, 'orders' => $orders,
            'utm' => $c['utm'] ? (json_decode((string) $c['utm'], true) ?: []) : [],
            'error' => Session::flash('anon_error'),
        ], 'panel');
    }

    public function anonymize(Request $request): Response
    {
        $id = (int) $request->param('id');
        $reason = $request->str('reason', 300);
        if ($request->input('confirm') !== '1' || mb_strlen($reason) < 4) {
            Session::flash('anon_error', 'Para anonimizar marca la confirmación y escribe el motivo (por ejemplo, la solicitud de supresión recibida por correo).');
            return Response::redirect('/panel/clientas/' . $id, 303);
        }
        $ok = AnonymizeService::anonymize($id, Auth::id(), $reason, $request->ip());
        Session::flash($ok ? 'notice' : 'error', $ok ? 'Datos de la clienta anonimizados. Sus reservas y comisiones se conservan.' : 'La clienta no existe o ya estaba anonimizada.');
        return Response::redirect('/panel/clientas/' . $id, 303);
    }
}
