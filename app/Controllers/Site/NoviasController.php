<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Domain\Catalog;
use App\Http\Request;
use App\Http\Response;
use App\Support\Schema;

final class NoviasController extends Controller
{
    public function index(Request $request): Response
    {
        $packs = Catalog::packs();
        $p3 = $packs['pack3'];
        $faq = [
            ['q' => '¿Cuánto cuesta el maquillaje de novia?', 'a' => money($p3['price']) . ' con el Pack 3, que incluye prueba previa, dos propuestas, video reel y extra fijación.'],
            ['q' => '¿Cuándo se hace la prueba previa?', 'a' => 'Unos días antes del evento, en el estudio de Floridablanca. Ahí te presentamos las dos propuestas y definimos juntas el look elegido.'],
            ['q' => '¿Maquillan en la locación de la boda?', 'a' => 'Sí, llevamos silla y luz de estudio a tu casa, hotel o finca en el área metropolitana. Fuera de Bucaramanga, Floridablanca, Piedecuesta y Girón, el desplazamiento se cotiza por WhatsApp según la distancia.'],
            ['q' => '¿Y si tengo que cambiar la fecha?', 'a' => 'Reagendamos tu cita. El abono del 50 % no se devuelve, pero se mantiene para la nueva fecha. Avísanos con mínimo un día de anticipación.'],
        ];
        // En el JSON-LD solo va el texto confirmado (sin marcadores pendientes)
        $faqSchema = array_map(static fn(array $i): array => ['q' => $i['q'], 'a' => $i['a']], $faq);

        return $this->page('novias', 'site/novias', [
            'packs' => $packs,
            'faq' => $faq,
            'css' => ['pages/novias.css'],
            'preload' => ['key' => 'fotos/novia-labios-espejo', 'sizes' => '100vw'],
            'schema' => [
                Schema::service(
                    $p3['name'],
                    'Maquillaje de novia y quinceañera en Bucaramanga y Floridablanca: ' . implode(', ', $p3['includes']) . '.',
                    (int) $p3['price'],
                    '/maquillaje-de-novia-bucaramanga',
                    'Maquillaje de novia',
                ),
                Schema::faq($faqSchema),
            ],
            'waOrigin' => 'novias',
        ]);
    }
}
