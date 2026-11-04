<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;

/**
 * Tienda en «próximamente»: sin catálogo ni pagos en línea. Los bonos de regalo se piden por WhatsApp.
 * La página no se indexa hasta que abra; las direcciones antiguas de producto redirigen aquí.
 */
final class TiendaController extends Controller
{
    public function index(Request $request): Response
    {
        return $this->page('tienda', 'site/tienda', [
            'css' => ['pages/tienda.css'],
            'waOrigin' => 'tienda',
            'meta' => ['robots' => 'noindex, follow'],
        ]);
    }

    /** /tienda/{lo-que-sea} (productos, pedidos) → /tienda */
    public function legacy(Request $request): Response
    {
        return Response::redirect('/tienda', 301);
    }
}
