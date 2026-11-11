<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

/**
 * Estudio y contacto. La cotización no se guarda: arma el mensaje y abre WhatsApp con él (§7).
 */
final class EstudioController extends Controller
{
    private const SERVICES = [
        'novia' => 'Novia · Pack 3',
        'quince' => 'Quinceañera · Pack 3',
        'social' => 'Social o grado · Pack 1',
        'domicilio' => 'A domicilio · Pack 2',
        'curso-auto' => 'Curso de automaquillaje',
        'curso-pro' => 'Curso de maquillaje profesional',
    ];

    /** Lugar del servicio con la tarifa de domicilio (siempre adicional; en el estudio no tiene costo). */
    private static function places(): array
    {
        $fees = (array) config('app.booking.home_fees');
        $out = ['estudio' => 'En el estudio, Floridablanca · sin costo'];
        foreach (['bucaramanga' => 'Bucaramanga', 'floridablanca' => 'Floridablanca', 'piedecuesta' => 'Piedecuesta', 'giron' => 'Girón'] as $k => $town) {
            $out[$k] = 'A domicilio en ' . $town . (isset($fees[$town]) ? ' · + ' . money((int) $fees[$town]) : '');
        }
        $out['rural'] = 'A domicilio, zona rural · a convenir';
        return $out;
    }

    public function index(Request $request): Response
    {
        return $this->render();
    }

    public function cotizar(Request $request): Response
    {
        $old = [
            'nombre' => $request->str('nombre', 80),
            'fecha' => $request->str('fecha', 10),
            'hora' => $request->str('hora', 5),
            'servicio' => $request->str('servicio', 20),
            'lugar' => $request->str('lugar', 20),
            'mensaje' => $request->str('mensaje', 400),
        ];

        $errors = [];
        if (mb_strlen($old['nombre']) < 2) {
            $errors['nombre'] = 'Escribe tu nombre.';
        }
        $tz = new \DateTimeZone((string) config('app.timezone'));
        $date = null;
        if ($old['fecha'] !== '') {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $old['fecha'], $tz);
            $today = new \DateTimeImmutable('today', $tz);
            $min = $today->modify('+' . (int) config('app.booking.min_days_ahead', 2) . ' days');
            if ($date === false || $date->format('Y-m-d') !== $old['fecha']) {
                $errors['fecha'] = 'Elige una fecha válida.';
                $date = null;
            } elseif ($date < $min) {
                $errors['fecha'] = 'Agendamos con mínimo 2 días de anticipación. Para hoy o mañana, escríbenos por WhatsApp.';
            } elseif ($date > $today->modify('+2 years')) {
                $errors['fecha'] = 'Elige una fecha dentro de los próximos dos años.';
            }
        }
        if ($old['hora'] !== '' && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $old['hora'])) {
            $errors['hora'] = 'Elige una hora válida.';
        }
        if (!isset(self::SERVICES[$old['servicio']])) {
            $errors['servicio'] = 'Elige un servicio de la lista.';
        }
        if (!isset(self::places()[$old['lugar']])) {
            $errors['lugar'] = 'Elige un lugar de la lista.';
        }
        if ($errors) {
            return $this->render($old, $errors, null, 422);
        }

        $when = $date ? fecha_larga($date) : null;
        if ($old['hora'] !== '') {
            $h = hora(\DateTimeImmutable::createFromFormat('H:i', $old['hora'], $tz) ?: new \DateTimeImmutable('now', $tz));
            $when = $when !== null ? $when . ', ' . $h : 'Hora: ' . $h;
        }
        $lines = ['Hola, Make Up by Fletcher. Quiero cotizar un servicio:', ''];
        $lines[] = '• Servicio: ' . self::SERVICES[$old['servicio']];
        if ($when !== null) {
            $lines[] = '• Fecha: ' . $when;
        }
        $lines[] = '• Lugar: ' . self::places()[$old['lugar']];
        $lines[] = '• Mi nombre: ' . $old['nombre'];
        if ($old['mensaje'] !== '') {
            $lines[] = '• Nota: ' . $old['mensaje'];
        }
        $lines[] = '';
        $lines[] = '¿Tienen disponibilidad?';
        \App\Support\Metrics::bump('quotes');

        return Response::redirect(wa_direct(implode("\n", $lines)), 303)->header('Cache-Control', 'no-store');
    }

    /** @param array<string,string> $old @param array<string,string> $errors */
    private function render(array $old = [], array $errors = [], ?string $formError = null, int $status = 200): Response
    {
        $tz = new \DateTimeZone((string) config('app.timezone'));
        return $this->page('estudio', 'site/estudio', [
            'old' => $old + ['servicio' => 'novia', 'lugar' => 'estudio'],
            'errors' => $errors,
            'formError' => $formError,
            'services' => self::SERVICES,
            'places' => self::places(),
            'minDate' => (new \DateTimeImmutable('today', $tz))->modify('+' . (int) config('app.booking.min_days_ahead', 2) . ' days')->format('Y-m-d'),
            'css' => ['pages/estudio.css', 'pages/mapa.css'],
            'js' => ['mapa.js'],
            'preload' => ['key' => 'fotos/catalogo-brindis-estudio', 'sizes' => '(max-width: 960px) 100vw, 580px'],
            'waOrigin' => 'estudio',
        ], $status);
    }
}
