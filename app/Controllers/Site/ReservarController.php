<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Domain\Catalog;
use App\Http\Request;
use App\Http\Response;
use App\Support\Metrics;
use App\Support\WhatsApp;

/**
 * /reservar: la clienta elige servicio, personas, fecha, hora y lugar, y se abre WhatsApp con la
 * solicitud ya escrita. La disponibilidad y el abono se confirman por chat. No se guarda nada en la BD.
 * Funciona sin JavaScript (POST → 303 a wa.me); reservar.js añade el total y la vista previa del mensaje.
 */
final class ReservarController extends Controller
{
    public function index(Request $request): Response
    {
        $packs = Catalog::packs();
        $old = [
            'service' => '', 'people' => '1', 'date' => '', 'time' => '', 'location' => 'studio',
            'zone' => '', 'event' => '', 'name' => '', 'notes' => '',
        ];
        $pre = $request->str('servicio', 10);
        if (isset($packs[$pre])) {
            $old['service'] = $pre;
        }

        if (!$request->isPost()) {
            Metrics::bump('booking_starts');
            return $this->render($old, []);
        }

        foreach (['service' => 10, 'people' => 2, 'date' => 10, 'time' => 5, 'location' => 6, 'zone' => 80, 'event' => 20, 'name' => 80, 'notes' => 400] as $f => $len) {
            $old[$f] = trim($request->str($f, $len));
        }
        $errors = $this->validate($old, $packs);
        if ($errors !== []) {
            return $this->render($old, $errors, 422);
        }
        Metrics::bump('booking_submits');
        return Response::redirect(wa_direct(WhatsApp::serviceRequest([
            'service' => $old['service'], 'people' => (int) $old['people'], 'date' => $old['date'], 'time' => $old['time'],
            'location' => $old['location'], 'zone' => $old['zone'], 'event' => $old['event'], 'name' => $old['name'], 'notes' => $old['notes'],
        ])), 303)->header('Cache-Control', 'no-store');
    }

    /** @return array<string,string> */
    private function validate(array $d, array $packs): array
    {
        $e = [];
        if (!isset($packs[$d['service']])) {
            $e['service'] = 'Elige el servicio que necesitas.';
        }
        $maxPeople = (int) config('app.booking.max_people', 4);
        if (!ctype_digit($d['people']) || (int) $d['people'] < 1 || (int) $d['people'] > $maxPeople) {
            $e['people'] = 'Indica cuántas personas se maquillan.';
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $d['date'], new \DateTimeZone('America/Bogota'));
        [$min, $max] = $this->dateRange();
        if (!$date || $date->format('Y-m-d') !== $d['date']) {
            $e['date'] = 'Elige la fecha de tu evento.';
        } elseif ($d['date'] < $min || $d['date'] > $max) {
            $e['date'] = 'Agendamos con mínimo ' . (int) config('app.booking.min_days_ahead', 2) . ' días de anticipación.';
        }
        if ($d['time'] !== '' && !in_array($d['time'], (array) config('app.booking.slots'), true)) {
            $e['time'] = 'Elige una hora de la lista.';
        }
        if (!in_array($d['location'], ['studio', 'home'], true)) {
            $e['location'] = 'Elige dónde será el maquillaje.';
        } elseif ($d['location'] === 'home' && mb_strlen($d['zone']) < 3) {
            $e['zone'] = 'Escribe el barrio y la ciudad del servicio.';
        }
        if ($d['event'] !== '' && !isset(config('app.booking.event_types')[$d['event']])) {
            $e['event'] = 'Elige el tipo de evento.';
        }
        if (mb_strlen($d['name']) < 2) {
            $e['name'] = 'Escribe tu nombre.';
        }
        return $e;
    }

    /** @return array{0:string,1:string} fechas mínima y máxima (Bogotá) */
    private function dateRange(): array
    {
        $today = new \DateTimeImmutable('today', new \DateTimeZone('America/Bogota'));
        return [
            $today->modify('+' . (int) config('app.booking.min_days_ahead', 2) . ' days')->format('Y-m-d'),
            $today->modify('+' . (int) config('app.booking.max_days_ahead', 540) . ' days')->format('Y-m-d'),
        ];
    }

    private function render(array $old, array $errors, int $status = 200): Response
    {
        [$min, $max] = $this->dateRange();
        return $this->page('reservar', 'site/reservar', [
            'old' => $old,
            'errors' => $errors,
            'packs' => Catalog::packs(),
            'minDate' => $min,
            'maxDate' => $max,
            'crumbs' => [],
            'css' => ['pages/reservar.css'],
            'js' => ['reservar.js'],
            'focus' => true,
            'hideFab' => true,
            'meta' => ['robots' => 'noindex, follow'],
        ], $status);
    }
}
