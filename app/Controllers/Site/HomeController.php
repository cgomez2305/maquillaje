<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Domain\Catalog;
use App\Http\Request;
use App\Http\Response;
use App\Support\Schema;

final class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $packs = Catalog::packs();
        $faq = [
            ['q' => '¿Cuánto cuesta un maquillaje de novia en Bucaramanga?', 'a' => 'El Pack 3 para novias y quinceañeras cuesta ' . money($packs['pack3']['price']) . ' e incluye prueba previa, dos propuestas de maquillaje, video reel y extra fijación. La mamá, las damas y las invitadas pueden sumar el Pack 1 (' . money($packs['pack1']['price']) . ') o el Pack 2 (' . money($packs['pack2']['price']) . ').'],
            ['q' => '¿Hacen maquillaje a domicilio?', 'a' => 'Sí. El Pack 2 (' . money($packs['pack2']['price']) . ') lleva silla y luz de estudio a tu casa u hotel, con skin care previo y kit de retoque.'],
            ['q' => '¿Cómo separo mi fecha?', 'a' => 'Arma tu solicitud en «Reservar» (servicio, fecha y lugar) y se abre WhatsApp con el mensaje listo, o escríbenos directamente. La fecha queda reservada con un abono del 50 % por Nequi o Bancolombia. Si no puedes asistir, reagendamos tu cita: el abono no se devuelve.'],
            ['q' => '¿Con cuánta anticipación debo reservar?', 'a' => 'Mínimo con 2 días de anticipación. Para bodas y quince años conviene más tiempo: las fechas de octubre a diciembre, temporada de bodas y grados, son las primeras en llenarse.'],
        ];

        return $this->page('home', 'site/home', [
            'packs' => $packs,
            'courses' => Catalog::courses(),
            'faq' => $faq,
            'css' => ['pages/home.css'],
            'js' => ['rail.js'],
            'preload' => ['key' => 'fotos/novia-tiara-retrato', 'sizes' => '(max-width: 960px) 80vw, 520px'],
            'schema' => [Schema::faq($faq)],
            'waOrigin' => 'inicio',
        ]);
    }
}
