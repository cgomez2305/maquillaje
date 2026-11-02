<?php
declare(strict_types=1);

use App\Controllers\Panel;
use App\Controllers\Site;
use App\Controllers\Webhooks\WompiController;
use App\Panel\Guard;
use App\Router;

/**
 * Rutas. Opciones: 'csrf' => false (solo webhooks), 'middleware' => [Clase::class],
 * 'roles' => ['owner','partner','staff'] (lo verifica App\Panel\Guard en el servidor).
 */
return static function (Router $r): void {
    // Sitio público
    $r->add('GET', '/', [Site\HomeController::class, 'index']);
    $r->add('GET', '/maquillaje-de-novia-bucaramanga', [Site\NoviasController::class, 'index']);
    $r->add('GET', '/precios-maquillaje-bucaramanga', [Site\ServiciosController::class, 'index']);
    $r->add('GET', '/cursos-de-maquillaje-bucaramanga', [Site\CursosController::class, 'index']);
    $r->add('GET', '/portafolio', [Site\PortafolioController::class, 'index']);
    $r->add('GET', '/estudio-maquillaje-floridablanca', [Site\EstudioController::class, 'index']);
    $r->add('POST', '/estudio-maquillaje-floridablanca/cotizar', [Site\EstudioController::class, 'cotizar']);
    $r->add('GET', '/tienda', [Site\TiendaController::class, 'index']);
    $r->add('GET', '/tienda/pedido/{reference:PED-\d{4}-\d{4}}', [Site\TiendaController::class, 'pedido']);
    $r->add('GET', '/tienda/{slug}', [Site\TiendaController::class, 'show']);
    $r->add('POST', '/tienda/{slug}/comprar', [Site\TiendaController::class, 'comprar']);
    $r->add('GET', '/diario', [Site\DiarioController::class, 'index']);
    $r->add('POST', '/diario/guia-novias', [Site\DiarioController::class, 'guia']);
    $r->add('GET', '/diario/confirmar/{token:[A-Za-z0-9_-]{43}}', [Site\DiarioController::class, 'confirmar']);
    $r->add('GET', '/diario/{slug}', [Site\DiarioController::class, 'show']);
    $r->add('GET|POST', '/reservar', [Site\ReservarController::class, 'index']);
    $r->add('GET', '/reservar/disponibilidad', [Site\ReservarController::class, 'disponibilidad']);
    $r->add('GET', '/reserva/{code:MBF-\d{4}-\d{4}}', [Site\ReservarController::class, 'estado']);
    $r->add('GET|POST', '/encuesta/{token:[A-Za-z0-9_-]{43}}', [Site\EncuestaController::class, 'index']);
    $r->add('GET', '/politica-de-privacidad', [Site\LegalController::class, 'privacidad']);
    $r->add('GET', '/terminos', [Site\LegalController::class, 'terminos']);
    $r->add('GET', '/sitemap.xml', [Site\SitemapController::class, 'sitemap']);
    $r->add('GET', '/robots.txt', [Site\SitemapController::class, 'robots']);
    $r->add('GET', '/wa', [Site\WhatsappController::class, 'go']);

    // Webhooks (sin CSRF: se validan por firma)
    $r->add('POST', '/webhooks/wompi', [WompiController::class, 'handle'], ['csrf' => false]);

    // Panel privado
    $all = ['middleware' => [Guard::class], 'roles' => ['owner', 'partner', 'staff']];
    $ownerPartner = ['middleware' => [Guard::class], 'roles' => ['owner', 'partner']];
    $ownerStaff = ['middleware' => [Guard::class], 'roles' => ['owner', 'staff']];
    $owner = ['middleware' => [Guard::class], 'roles' => ['owner']];

    $r->add('GET|POST', '/panel/login', [Panel\AuthController::class, 'login']);
    $r->add('GET|POST', '/panel/login/2fa', [Panel\AuthController::class, 'twoFactor']);
    $r->add('GET|POST', '/panel/recuperar', [Panel\AuthController::class, 'forgot']);
    $r->add('GET|POST', '/panel/recuperar/{token:[A-Za-z0-9_-]{43}}', [Panel\AuthController::class, 'reset']);
    $r->add('POST', '/panel/salir', [Panel\AuthController::class, 'logout'], ['middleware' => [Guard::class], 'roles' => ['owner', 'partner', 'staff'], 'allow_setup' => true]);
    $r->add('GET|POST', '/panel/seguridad', [Panel\AuthController::class, 'security'], ['middleware' => [Guard::class], 'roles' => ['owner', 'partner', 'staff'], 'allow_setup' => true]);

    $r->add('GET', '/panel', [Panel\DashboardController::class, 'index'], $ownerPartner);
    $r->add('GET', '/panel/reservas', [Panel\ReservasController::class, 'index'], $all);
    $r->add('GET|POST', '/panel/reservas/nueva', [Panel\ReservasController::class, 'create'], $ownerStaff);
    $r->add('GET', '/panel/reservas/{code:MBF-\d{4}-\d{4}}', [Panel\ReservasController::class, 'show'], $all);
    $r->add('POST', '/panel/reservas/{code:MBF-\d{4}-\d{4}}/estado', [Panel\ReservasController::class, 'transition'], $ownerStaff);
    $r->add('POST', '/panel/reservas/{code:MBF-\d{4}-\d{4}}/editar', [Panel\ReservasController::class, 'update'], $ownerStaff);
    $r->add('POST', '/panel/reservas/{code:MBF-\d{4}-\d{4}}/comprobante', [Panel\ArchivosController::class, 'upload'], $ownerStaff);
    $r->add('GET', '/panel/archivos/{id:\d+}', [Panel\ArchivosController::class, 'download'], $all);
    $r->add('GET', '/panel/calendario', [Panel\CalendarioController::class, 'index'], $ownerStaff);
    $r->add('GET', '/panel/clientas', [Panel\ClientasController::class, 'index'], $ownerPartner);
    $r->add('GET', '/panel/clientas/{id:\d+}', [Panel\ClientasController::class, 'show'], $ownerPartner);
    $r->add('POST', '/panel/clientas/{id:\d+}/anonimizar', [Panel\ClientasController::class, 'anonymize'], $owner);
    $r->add('GET', '/panel/ventas', [Panel\VentasController::class, 'index'], $ownerPartner);
    $r->add('POST', '/panel/ventas/canjear', [Panel\VentasController::class, 'redeem'], $owner);
    $r->add('GET', '/panel/comisiones', [Panel\ComisionesController::class, 'index'], $ownerPartner);
    $r->add('POST', '/panel/comisiones/{id:\d+}/aprobar', [Panel\ComisionesController::class, 'approve'], $ownerPartner);
    $r->add('POST', '/panel/comisiones/{id:\d+}/comentar', [Panel\ComisionesController::class, 'comment'], $ownerPartner);
    $r->add('POST', '/panel/comisiones/{id:\d+}/pagado', [Panel\ComisionesController::class, 'markPaid'], $owner);
    $r->add('GET', '/panel/comisiones/{id:\d+}/exportar.csv', [Panel\ComisionesController::class, 'exportCsv'], $ownerPartner);
    $r->add('GET', '/panel/comisiones/{id:\d+}/imprimir', [Panel\ComisionesController::class, 'printable'], $ownerPartner);
    $r->add('POST', '/panel/comisiones/tasas', [Panel\ComisionesController::class, 'proposeRate'], $ownerPartner);
    $r->add('POST', '/panel/comisiones/tasas/{id:\d+}/aprobar', [Panel\ComisionesController::class, 'approveRate'], $ownerPartner);
    $r->add('GET', '/panel/historial', [Panel\HistorialController::class, 'index'], $ownerPartner);
    $r->add('GET', '/panel/historial/verificar', [Panel\HistorialController::class, 'verify'], $ownerPartner);
    $r->add('GET|POST', '/panel/ajustes', [Panel\AjustesController::class, 'index'], $owner);
    $r->add('GET', '/panel/diario', [Panel\DiarioController::class, 'index'], $owner);
    $r->add('GET|POST', '/panel/diario/nuevo', [Panel\DiarioController::class, 'edit'], $owner);
    $r->add('GET|POST', '/panel/diario/{id:\d+}', [Panel\DiarioController::class, 'edit'], $owner);
};
