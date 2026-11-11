<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Domain\Catalog;
use App\Http\Request;
use App\Http\Response;
use App\Support\Schema;

final class ServiciosController extends Controller
{
    public function index(Request $request): Response
    {
        $packs = Catalog::packs();
        $path = '/precios-maquillaje-bucaramanga';
        $types = ['pack1' => 'Maquillaje social', 'pack2' => 'Maquillaje a domicilio', 'pack3' => 'Maquillaje de novia'];
        $schema = [];
        foreach ($packs as $code => $p) {
            $schema[] = Schema::service(
                $p['name'],
                implode('. ', $p['includes']) . '.',
                (int) $p['price'],
                $code === 'pack3' ? '/maquillaje-de-novia-bucaramanga' : $path,
                $types[$code] ?? 'Maquillaje profesional',
            );
        }

        return $this->page('servicios', 'site/servicios', [
            'packs' => $packs,
            'css' => ['pages/servicios.css', 'pages/mapa.css'],
            'js' => ['mapa.js'],
            'schema' => $schema,
            'waOrigin' => 'servicios',
        ]);
    }
}
