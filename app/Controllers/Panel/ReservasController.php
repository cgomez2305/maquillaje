<?php
declare(strict_types=1);

namespace App\Controllers\Panel;

use App\Controllers\Controller;
use App\Domain\Audit\EventLog;
use App\Domain\Booking\BookingAdminService;
use App\Domain\Booking\BookingService;
use App\Domain\Catalog;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Session;
use App\Panel\Auth;
use App\Support\Settings;

/** /panel/reservas: listado, detalle lateral, transiciones, edición y alta manual (Panel-Reservas.dc.html). */
final class ReservasController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->render($request, null);
    }

    public function show(Request $request): Response
    {
        $b = BookingService::findByCode($request->param('code'));
        if ($b === null) {
            throw new HttpException(404);
        }
        return $this->render($request, $b);
    }

    public function transition(Request $request): Response
    {
        $code = $request->param('code');
        $error = BookingAdminService::transition(
            $code,
            $request->str('to', 20),
            $request->str('reason', 500),
            $request->str('date', 10) ?: null,
            $request->str('time', 5) ?: null,
            $this->actor($request),
        );
        Session::flash($error === null ? 'notice' : 'error', $error ?? 'Cambio guardado y registrado en el historial.');
        return Response::redirect('/panel/reservas/' . $code . $this->backQuery($request), 303);
    }

    public function update(Request $request): Response
    {
        $code = $request->param('code');
        $error = BookingAdminService::update($code, [
            'service' => $request->str('service', 10),
            'people' => $request->int('people', 1),
            'total' => $request->str('total', 12),
            'notes' => $request->str('notes', 1000),
            'address' => $request->str('address', 255),
            'reason' => $request->str('reason', 500),
        ], $this->actor($request));
        Session::flash($error === null ? 'notice' : 'error', $error ?? 'Reserva actualizada. El cambio quedó en el historial.');
        return Response::redirect('/panel/reservas/' . $code . $this->backQuery($request), 303);
    }

    public function create(Request $request): Response
    {
        $errors = [];
        $old = [];
        if ($request->isPost()) {
            $fields = ['phone' => 30, 'name' => 120, 'email' => 190, 'birthday' => 10, 'service' => 10, 'location' => 6, 'address' => 255,
                'date' => 10, 'time' => 5, 'event_type' => 20, 'first_source' => 20, 'lead_code' => 20, 'notes' => 1000];
            foreach ($fields as $f => $len) {
                $old[$f] = $request->str($f, $len);
            }
            $old['people'] = $request->int('people', 1);
            $old['consent'] = $request->input('consent') === '1';
            $old['deposit_received'] = $request->input('deposit_received') === '1';
            $result = BookingAdminService::createManual($old, $this->actor($request));
            if ($result['booking'] !== null) {
                $b = $result['booking'];
                $msg = 'Reserva ' . $b['code'] . ' registrada. Origen: ' . \App\Domain\Attribution\Source::from($b['source'])->label()
                    . ((int) $b['is_web_attributed'] ? ' (atribuida a la web).' : '.');
                Session::flash('notice', $msg);
                return Response::redirect('/panel/reservas/' . $b['code'], 303);
            }
            $errors = $result['errors'];
        }
        return $this->view('panel/reserva-nueva', [
            'title' => 'Nueva reserva manual',
            'active' => 'reservas',
            'errors' => $errors,
            'old' => $old + ['people' => 1, 'location' => 'studio', 'service' => 'pack1'],
            'packs' => Catalog::packs(),
            'slots' => Settings::slots(),
        ], 'panel', $errors ? 422 : 200);
    }

    private function render(Request $request, ?array $cur): Response
    {
        $filter = $request->str('estado', 20);
        if (!array_key_exists($filter, BookingAdminService::FILTERS)) {
            $filter = '';
        }
        $q = $request->str('q', 80);
        $sort = $request->str('orden', 12) ?: 'fecha';
        $list = BookingAdminService::list($filter, $q, $sort, $request->int('p', 1));
        $role = (string) Auth::role();
        $data = [
            'title' => $cur ? 'Reserva ' . $cur['code'] : 'Reservas',
            'active' => 'reservas',
            'list' => $list,
            'filter' => $filter,
            'q' => $q,
            'sort' => $sort,
            'role' => $role,
            'cur' => $cur,
            'back' => $this->backQuery($request),
        ];
        if ($cur !== null) {
            $data['events'] = EventLog::forBooking((int) $cur['id']);
            $data['files'] = BookingAdminService::files((int) $cur['id']);
            $data['transitions'] = BookingAdminService::allowedTransitions($cur, $role);
            $data['canEdit'] = BookingAdminService::canEdit($cur, $role);
            $data['canEditValue'] = BookingAdminService::canEditValue($cur);
            $data['packs'] = Catalog::packs();
            $data['slots'] = Settings::slots();
        }
        return $this->view('panel/reservas', $data, 'panel');
    }

    /** @return array{id:int,role:string,ip:string,ua:string} */
    private function actor(Request $request): array
    {
        return ['id' => (int) Auth::id(), 'role' => (string) Auth::role(), 'ip' => $request->ip(), 'ua' => $request->userAgent()];
    }

    /** Conserva filtro y búsqueda al volver al listado. */
    private function backQuery(Request $request): string
    {
        $q = array_filter([
            'estado' => $request->str('estado', 20),
            'q' => $request->str('q', 80),
            'orden' => $request->str('orden', 12),
            'p' => $request->int('p', 0) > 1 ? (string) $request->int('p') : '',
        ], static fn($v) => $v !== '');
        return $q ? '?' . http_build_query($q) : '';
    }
}
