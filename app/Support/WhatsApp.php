<?php
declare(strict_types=1);

namespace App\Support;

use App\Domain\Catalog;

/**
 * Mensajes predeterminados de WhatsApp. Todas las solicitudes del sitio (reserva, cotización, curso,
 * bono, guía) terminan en un chat con el texto ya escrito: la clienta solo pulsa «Enviar».
 * El servidor no guarda los datos personales; solo arma el texto y redirige a wa.me.
 */
final class WhatsApp
{
    /** Tema → frase. Se usa en los botones de cada página (/wa?t=tema). */
    public const TOPICS = [
        'novias' => 'Quiero información del maquillaje de novia y separar mi fecha.',
        'quince' => 'Quiero información del maquillaje para quince años.',
        'grados' => 'Quiero reservar maquillaje para mi grado.',
        'domicilio' => 'Quiero pedir un servicio de maquillaje a domicilio.',
        'social' => 'Quiero reservar un maquillaje social.',
        'cursos' => 'Quiero información de los cursos de maquillaje.',
        'curso-auto' => 'Quiero inscribirme en el curso de automaquillaje. ¿Cuándo empieza el próximo grupo?',
        'curso-pro' => 'Quiero inscribirme en el curso de maquillaje profesional. ¿Cuándo empieza el próximo grupo?',
        'bono' => 'Quiero regalar un bono de maquillaje. ¿Qué opciones tienen?',
        'tienda' => 'Quiero saber cuándo abre la tienda y qué productos recomiendan.',
        'guia' => 'Quiero recibir la guía de belleza para novias.',
        'estudio' => 'Quiero agendar una cita en el estudio de Floridablanca.',
    ];

    public static function topicText(?string $topic): string
    {
        $text = 'Hola, Make Up by Fletcher. Vengo de la página web.';
        if ($topic !== null && isset(self::TOPICS[$topic])) {
            $text .= ' ' . self::TOPICS[$topic];
        }
        return $text;
    }

    /**
     * Solicitud de servicio armada desde /reservar o la cotización del estudio.
     * @param array{service?:string,people?:int,date?:?string,time?:?string,location?:string,town?:string,zone?:string,event?:string,name?:string,notes?:string} $d
     */
    public static function serviceRequest(array $d): string
    {
        $packs = Catalog::packs();
        $names = ['pack1' => 'Pack 1 · Maquillaje social', 'pack2' => 'Pack 2 · Full experience a domicilio', 'pack3' => 'Pack 3 · Novias y quinceañeras'];
        $code = (string) ($d['service'] ?? '');
        $people = max(1, (int) ($d['people'] ?? 1));
        $maxPeople = (int) config('app.booking.max_people', 4);
        $lines = ['Hola, Make Up by Fletcher. Quiero solicitar un servicio:', ''];
        if (isset($packs[$code])) {
            $lines[] = '• Servicio: ' . $names[$code] . ' (' . money((int) $packs[$code]['price']) . ' por persona)';
        }
        $lines[] = '• Personas: ' . ($people >= $maxPeople ? $maxPeople . ' o más' : $people);
        if (!empty($d['event']) && isset(config('app.booking.event_types')[$d['event']])) {
            $lines[] = '• Evento: ' . config('app.booking.event_types')[$d['event']];
        }
        if (!empty($d['date'])) {
            $date = \DateTimeImmutable::createFromFormat('!Y-m-d', (string) $d['date']);
            $lines[] = '• Fecha: ' . ($date ? fecha_larga($date, true) : $d['date']);
        }
        if (!empty($d['time'])) {
            $lines[] = '• Hora de inicio: ' . hora(new \DateTimeImmutable('2000-01-01 ' . $d['time']));
        }
        $home = ($d['location'] ?? 'studio') === 'home';
        $town = (string) ($d['town'] ?? '');
        $fees = (array) config('app.booking.home_fees');
        $fee = $home ? (int) ($fees[$town] ?? 0) : 0;
        if ($home) {
            $place = array_filter([(string) ($d['zone'] ?? ''), $town === 'rural' ? 'zona rural' : $town]);
            $lines[] = '• Lugar: A domicilio' . ($place !== [] ? ' · ' . implode(', ', $place) : '');
            $lines[] = '• Domicilio: ' . ($fee > 0 ? money($fee) . ' (adicional)' : 'a convenir');
        } else {
            $lines[] = '• Lugar: En el estudio de Floridablanca (sin costo de domicilio)';
        }
        if (isset($packs[$code])) {
            $total = (int) $packs[$code]['price'] * min($people, $maxPeople) + $fee;
            $lines[] = '• Total estimado: ' . money($total) . ($people >= $maxPeople ? ' o más' : '') . ($fee > 0 ? ' con domicilio' : '');
        }
        if (!empty($d['name'])) {
            $lines[] = '• Mi nombre: ' . $d['name'];
        }
        if (!empty($d['notes'])) {
            $lines[] = '• Nota: ' . $d['notes'];
        }
        $lines[] = '';
        $lines[] = '¿Tienen disponibilidad?';
        return implode("\n", $lines);
    }
}
