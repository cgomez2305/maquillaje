<?php
declare(strict_types=1);

namespace App\Controllers\Panel;

use App\Controllers\Controller;
use App\Domain\Audit\AuditLog;
use App\Domain\Commission\CommissionService;
use App\Domain\Commission\RateService;
use App\Domain\Commission\ReportService;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Session;
use App\Panel\Auth;
use App\Support\Db;

/**
 * /panel/comisiones (§9.4, §9.5): liquidación del corte, aprobación doble, comentarios,
 * pago, tasas con doble aprobación y exportaciones.
 */
final class ComisionesController extends Controller
{
    public function index(Request $request): Response
    {
        $now = local_time(now_utc());
        CommissionService::ensurePeriod((int) $now->format('Y'), (int) $now->format('n'));
        $id = $request->int('periodo', 0);
        $p = $id > 0 ? Db::one('SELECT * FROM commission_periods WHERE id = ?', [$id]) : ReportService::defaultPeriod();
        if ($p === null) {
            throw new HttpException(404);
        }
        $role = (string) Auth::role();
        return $this->view('panel/comisiones', [
            'title' => 'Comisiones',
            'active' => 'comisiones',
            'p' => $p,
            'x' => ReportService::periodDetail($p),
            'periods' => ReportService::periods(),
            'rates' => RateService::allCurrent(),
            'pendingRates' => ReportService::pendingRates(),
            'scheduledRates' => ReportService::scheduledRates(),
            'role' => $role,
            'canApprove' => $p['status'] === 'en_aprobacion' && $p[$role === 'owner' ? 'owner_approved_at' : 'partner_approved_at'] === null,
            'today' => $now->format('Y-m-d'),
        ], 'panel');
    }

    public function approve(Request $request): Response
    {
        $id = (int) $request->param('id');
        $error = CommissionService::approve($id, (string) Auth::role());
        if ($error === null) {
            $p = Db::one('SELECT status FROM commission_periods WHERE id = ?', [$id]);
            AuditLog::record(Auth::id(), 'corte_aprobado', 'periodo:' . $id, ['estado' => $p['status'] ?? null], $request->ip());
            Session::flash('notice', ($p['status'] ?? '') === 'cerrado' ? 'Aprobaste el corte. Con las dos aprobaciones quedó cerrado.' : 'Aprobaste el corte. Falta la otra parte.');
        } else {
            Session::flash('error', $error);
        }
        return Response::redirect('/panel/comisiones?periodo=' . $id, 303);
    }

    public function comment(Request $request): Response
    {
        $id = (int) $request->param('id');
        $body = $request->str('body', 1000);
        if (Db::value('SELECT id FROM commission_periods WHERE id = ?', [$id]) === null) {
            throw new HttpException(404);
        }
        if (mb_strlen($body) < 2) {
            Session::flash('error', 'Escribe el comentario.');
        } else {
            Db::run('INSERT INTO commission_comments (period_id, user_id, body, created_at) VALUES (?, ?, ?, ?)', [$id, Auth::id(), $body, now_utc()]);
            AuditLog::record(Auth::id(), 'corte_comentado', 'periodo:' . $id, null, $request->ip());
            Session::flash('notice', 'Comentario agregado.');
        }
        return Response::redirect('/panel/comisiones?periodo=' . $id . '#comentarios', 303);
    }

    public function markPaid(Request $request): Response
    {
        $id = (int) $request->param('id');
        $note = $request->str('note', 255);
        $error = CommissionService::markPaid($id, $note);
        if ($error === null) {
            AuditLog::record(Auth::id(), 'corte_pagado', 'periodo:' . $id, ['nota' => $note], $request->ip());
            Session::flash('notice', 'Corte marcado como pagado.');
        } else {
            Session::flash('error', $error);
        }
        return Response::redirect('/panel/comisiones?periodo=' . $id, 303);
    }

    public function proposeRate(Request $request): Response
    {
        $cat = $request->str('category', 20);
        $pct = str_replace(',', '.', $request->str('percent', 10));
        $from = $request->str('valid_from', 10);
        $today = local_time(now_utc())->format('Y-m-d');
        $error = null;
        if (!array_key_exists($cat, RateService::CATEGORIES)) {
            $error = 'Elige la categoría.';
        } elseif (!preg_match('/^\d{1,2}(\.\d{1,2})?$/', $pct) || (float) $pct > 50) {
            $error = 'Escribe un porcentaje entre 0 y 50, con hasta dos decimales.';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || $from < $today) {
            $error = 'La fecha de vigencia debe ser hoy o posterior.';
        }
        if ($error !== null) {
            Session::flash('error', $error);
            return Response::redirect('/panel/comisiones#tasas', 303);
        }
        $bp = (int) round((float) $pct * 100);
        $validFrom = (new \DateTimeImmutable($from . ' 00:00:00', new \DateTimeZone((string) config('app.timezone'))))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        $role = (string) Auth::role();
        $now = now_utc();
        $id = Db::insert(
            'INSERT INTO commission_rates (category, rate_bp, valid_from, proposed_by, approved_by_owner_at, approved_by_partner_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$cat, $bp, $validFrom, Auth::id(), $role === 'owner' ? $now : null, $role === 'partner' ? $now : null, $now],
        );
        AuditLog::record(Auth::id(), 'tasa_propuesta', 'tasa:' . $id, ['categoria' => $cat, 'rate_bp' => $bp, 'desde' => $from], $request->ip());
        Session::flash('notice', 'Propuesta registrada. Rige cuando la otra parte la apruebe.');
        return Response::redirect('/panel/comisiones#tasas', 303);
    }

    public function approveRate(Request $request): Response
    {
        $id = (int) $request->param('id');
        $role = (string) Auth::role();
        $col = $role === 'owner' ? 'approved_by_owner_at' : 'approved_by_partner_at';
        $r = Db::one('SELECT * FROM commission_rates WHERE id = ?', [$id]);
        if ($r === null) {
            throw new HttpException(404);
        }
        if ($r[$col] !== null) {
            Session::flash('error', 'Ya aprobaste esta tasa.');
        } else {
            Db::run("UPDATE commission_rates SET {$col} = ? WHERE id = ? AND {$col} IS NULL", [now_utc(), $id]);
            AuditLog::record(Auth::id(), 'tasa_aprobada', 'tasa:' . $id, ['categoria' => $r['category'], 'rate_bp' => (int) $r['rate_bp']], $request->ip());
            Session::flash('notice', 'Tasa aprobada.');
        }
        return Response::redirect('/panel/comisiones#tasas', 303);
    }

    public function exportCsv(Request $request): Response
    {
        $p = $this->periodOr404((int) $request->param('id'));
        $x = ReportService::periodDetail($p);
        $out = fopen('php://temp', 'w+');
        fwrite($out, "\xEF\xBB\xBF");
        $put = static function (array $row) use ($out): void {
            // Neutraliza fórmulas en hojas de cálculo (CSV injection)
            $row = array_map(static fn($v): string => is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'" . $v : (string) $v, $row);
            fputcsv($out, $row, ';', '"', '');
        };
        $put(['Corte', ReportService::periodName($p), 'Estado', CommissionService::STATUS_LABELS[$p['status']] ?? $p['status'], 'Hash', $p['snapshot_hash'] ?? '']);
        $put(['Tipo', 'Código', 'Detalle', 'Fecha', 'Origen', 'Base COP', 'Tasa (pb)', 'Comisión COP', 'Nota']);
        foreach ($x['services'] as $l) {
            $put(['Reserva', $l['code'], ReportService::serviceShort((string) $l['service_code'], (int) $l['people'], $l['event_type']), local_time($l['starts_at'])?->format('Y-m-d') ?? '', ReportService::originLabel((string) $l['source']), (int) $l['base'], (int) $l['rate_bp'], (int) $l['amount'], $l['note'] ?? '']);
        }
        foreach ($x['orders'] as $l) {
            $put(['Pedido', $l['reference'], $l['products'] ?? '', $l['paid_at'] ? local_time($l['paid_at'])->format('Y-m-d') : '', 'Tienda en línea', (int) $l['base'], (int) $l['rate_bp'], (int) $l['amount'], $l['note'] ?? '']);
        }
        $put(['Total', '', '', '', '', $x['servicesBase'] + $x['ordersBase'], '', $x['servicesAmount'] + $x['ordersAmount'], '']);
        rewind($out);
        $csv = (string) stream_get_contents($out);
        AuditLog::record(Auth::id(), 'exportacion_csv', 'periodo:' . $p['id'], null, $request->ip());
        $name = sprintf('comisiones-%04d-%02d.csv', $p['year'], $p['month']);
        return (new Response($csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8']))
            ->header('Content-Disposition', 'attachment; filename="' . $name . '"');
    }

    public function printable(Request $request): Response
    {
        $p = $this->periodOr404((int) $request->param('id'));
        AuditLog::record(Auth::id(), 'exportacion_pdf', 'periodo:' . $p['id'], null, $request->ip());
        return Response::html(\App\View::render('panel/comisiones-imprimir', [
            'p' => $p, 'x' => ReportService::periodDetail($p),
        ], null));
    }

    private function periodOr404(int $id): array
    {
        $p = Db::one('SELECT * FROM commission_periods WHERE id = ?', [$id]);
        if ($p === null) {
            throw new HttpException(404);
        }
        return $p;
    }
}
