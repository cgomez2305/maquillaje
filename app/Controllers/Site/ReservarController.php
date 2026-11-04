<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Domain\Booking\Availability;
use App\Domain\Booking\BookingService;
use App\Domain\Booking\BookingStateMachine;
use App\Domain\Booking\SlotTakenException;
use App\Domain\Catalog;
use App\Http\FormGuard;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Session;
use App\Support\Metrics;
use DateTimeImmutable;

/**
 * /reservar (§7.1): tres pasos + confirmación. Cada paso es un POST (funciona sin JavaScript);
 * reservar.js actualiza el resumen y la disponibilidad de horas sin recargar.
 */
final class ReservarController extends Controller
{
    private const KEY = 'reserva';

    public function index(Request $request): Response
    {
        $state = $this->state();

        if ($request->isPost()) {
            return $this->handlePost($request, $state);
        }

        $pre = $request->str('servicio', 10);
        if (in_array($pre, BookingService::PACKS, true)) {
            $state['service'] = $pre;
            $state['step'] = 1;
            $this->save($state);
            return Response::redirect('/reservar', 303);
        }
        if (empty($state['started'])) {
            $state['started'] = true;
            $this->save($state);
            Metrics::bump('booking_starts');
        }
        return $this->render($state, []);
    }

    /** JSON: horas libres de un día (para reservar.js). */
    public function disponibilidad(Request $request): Response
    {
        $date = $request->str('date', 10);
        $people = max(1, min(4, $request->int('people', 1)));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return Response::json(['slots' => []], 422);
        }
        return Response::json(['date' => $date, 'slots' => Availability::slotsForDay($date, $people)]);
    }

    /** /reserva/{code}?t=token — estado y confirmación para la clienta. */
    public function estado(Request $request): Response
    {
        $code = $request->param('code');
        $token = $request->str('t', 60);
        $b = BookingService::findByCode($code);
        if ($b === null || $token === '' || !hash_equals((string) $b['public_token'], $token)) {
            throw new HttpException(404);
        }
        $start = local_time($b['starts_at']);
        return $this->page('reservar', 'site/reserva-estado', [
            'b' => $b,
            'isNew' => $request->str('nueva', 2) === '1',
            'fecha' => $start ? fecha_larga($start) : '',
            'hora' => $start ? hora($start) : '',
            'statusLabel' => BookingStateMachine::LABELS[$b['status']] ?? $b['status'],
            'waUrl' => wa_direct('Hola, envío el comprobante del abono de mi reserva ' . $b['code']),
            'meta' => ['title' => 'Reserva ' . $b['code'] . ' · Make Up by Fletcher', 'description' => 'Estado de tu reserva', 'robots' => 'noindex, nofollow', 'canonical' => '/reservar'],
            'crumbs' => [],
            'css' => ['pages/reservar.css'],
            'focus' => true,
            'hideFab' => true,
        ]);
    }

    private function handlePost(Request $request, array $state): Response
    {
        $step = max(1, min(3, $request->int('step', (int) $state['step'])));
        $action = $request->str('action', 10);
        $mes = $request->str('mes', 7);

        // Guardar lo enviado en este paso (aunque se vaya hacia atrás)
        if ($step === 1) {
            $state['service'] = $request->str('service', 10) ?: $state['service'];
            $state['people'] = $request->int('people', (int) $state['people']);
        } elseif ($step === 2) {
            foreach (['date' => 10, 'time' => 5, 'location' => 6] as $f => $len) {
                $v = $request->str($f, $len);
                if ($v !== '') {
                    $state[$f] = $v;
                }
            }
        } else {
            foreach (['name' => 120, 'phone' => 30, 'email' => 190, 'birthday' => 10, 'address' => 255, 'event_type' => 20, 'how_found' => 20, 'notes' => 1000] as $f => $len) {
                $state['details'][$f] = $request->str($f, $len);
            }
            $state['details']['consent'] = $request->input('consent') === '1';
            $state['details']['marketing'] = $request->input('marketing') === '1';
        }

        if ($mes !== '' && preg_match('/^\d{4}-\d{2}$/', $mes)) {
            $state['month'] = $mes;
            $state['step'] = 2;
            $this->save($state);
            return Response::redirect('/reservar#calendario', 303);
        }

        if ($action === 'back') {
            $state['step'] = max(1, $step - 1);
            $this->save($state);
            return Response::redirect('/reservar#paso', 303);
        }

        $errors = match ($step) {
            1 => BookingService::validateService($state),
            2 => BookingService::validateSchedule($state),
            default => [],
        };
        if ($step === 3) {
            [$errors, $clean] = BookingService::validateDetails($state['details'], (string) $state['location']);
            $errors += BookingService::validateService($state) + BookingService::validateSchedule($state);
            if ($errors === []) {
                $spam = FormGuard::check($request, 'reserva');
                if ($spam !== null) {
                    $errors['form'] = $spam;
                }
            }
            if ($errors === []) {
                try {
                    $booking = BookingService::createFromWeb([
                        'service' => $state['service'], 'people' => (int) $state['people'], 'date' => $state['date'],
                        'time' => $state['time'], 'location' => $state['location'],
                    ], $clean, $request->ip(), $request->userAgent());
                } catch (SlotTakenException) {
                    $state['step'] = 2;
                    $state['time'] = null;
                    $this->save($state);
                    return $this->render($state, ['time' => 'Alguien acaba de tomar esa hora. Elige otra, por favor.'], 409);
                }
                Session::forget(self::KEY);
                Session::flash('booking_done', ['service' => $booking['service_code'], 'total' => (int) $booking['total']]);
                return Response::redirect('/reserva/' . $booking['code'] . '?t=' . $booking['public_token'] . '&nueva=1', 303);
            }
        }

        if ($errors !== []) {
            $state['step'] = $step;
            $this->save($state);
            return $this->render($state, $errors, 422);
        }

        $state['step'] = $step + 1;
        $this->save($state);
        // Entre pasos se aterriza en el indicador de progreso (en móvil el título ocupa la primera pantalla)
        return Response::redirect('/reservar#paso', 303);
    }

    private function render(array $state, array $errors, int $status = 200): Response
    {
        $tz = Availability::tz();
        $first = Availability::firstDay();
        $last = Availability::lastDay();
        $month = $state['month'] ?? null;
        if ($month === null && !empty($state['date'])) {
            $month = substr((string) $state['date'], 0, 7);
        }
        $monthStart = new DateTimeImmutable(($month ?? $first->format('Y-m')) . '-01', $tz);
        if ($monthStart < $first->modify('first day of this month')) {
            $monthStart = $first->modify('first day of this month');
        }
        if ($monthStart > $last) {
            $monthStart = $last->modify('first day of this month');
        }
        $people = max(1, (int) $state['people']);
        $days = (int) $state['step'] === 2 ? Availability::month((int) $monthStart->format('Y'), (int) $monthStart->format('n'), $people) : [];
        $slots = \App\Support\Settings::slots();
        $freeSlots = (!empty($state['date']) && (int) $state['step'] === 2) ? Availability::slotsForDay((string) $state['date'], $people) : $slots;
        $quote = in_array($state['service'], BookingService::PACKS, true) ? BookingService::quote((string) $state['service'], $people) : null;

        return $this->page('reservar', 'site/reservar', [
            's' => $state,
            'errors' => $errors,
            'packs' => Catalog::packs(),
            'quote' => $quote,
            'monthStart' => $monthStart,
            'prevMonth' => $monthStart > $first->modify('first day of this month') ? $monthStart->modify('-1 month')->format('Y-m') : null,
            'nextMonth' => $monthStart->modify('+1 month') <= $last ? $monthStart->modify('+1 month')->format('Y-m') : null,
            'days' => $days,
            'slots' => $slots,
            'freeSlots' => $freeSlots,
            'crumbs' => [],
            'css' => ['pages/reservar.css'],
            'js' => ['reservar.js'],
            'focus' => true,
            'hideFab' => true,
            'meta' => ['robots' => 'index, follow'],
        ], $status);
    }

    /** @return array<string,mixed> */
    private function state(): array
    {
        $s = Session::get(self::KEY);
        $default = ['step' => 1, 'service' => '', 'people' => 1, 'date' => null, 'time' => null, 'location' => 'studio', 'month' => null, 'details' => [], 'started' => false];
        return is_array($s) ? $s + $default : $default;
    }

    private function save(array $state): void
    {
        Session::put(self::KEY, $state);
    }
}
