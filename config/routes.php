<?php
declare(strict_types=1);

use App\Controllers\Site;
use App\Router;

/**
 * Rutas del sitio público. Sin panel ni pagos en línea: las solicitudes (reserva, cotización, curso, bono)
 * se envían a WhatsApp con un mensaje predeterminado y no se guardan datos personales en el servidor.
 */
return static function (Router $r): void {
    $r->add('GET', '/', [Site\HomeController::class, 'index']);
    $r->add('GET', '/maquillaje-de-novia-bucaramanga', [Site\NoviasController::class, 'index']);
    $r->add('GET', '/maquillaje-para-quinceaneras-bucaramanga', [Site\LandingController::class, 'quinceaneras']);
    $r->add('GET', '/maquillaje-para-grados-bucaramanga', [Site\LandingController::class, 'grados']);
    $r->add('GET', '/maquillaje-a-domicilio-bucaramanga', [Site\LandingController::class, 'domicilio']);
    $r->add('GET', '/precios-maquillaje-bucaramanga', [Site\ServiciosController::class, 'index']);
    $r->add('GET', '/cursos-de-maquillaje-bucaramanga', [Site\CursosController::class, 'index']);
    $r->add('GET', '/portafolio', [Site\PortafolioController::class, 'index']);
    $r->add('GET', '/estudio-maquillaje-floridablanca', [Site\EstudioController::class, 'index']);
    $r->add('POST', '/estudio-maquillaje-floridablanca/cotizar', [Site\EstudioController::class, 'cotizar']);
    $r->add('GET', '/tienda', [Site\TiendaController::class, 'index']);
    $r->add('GET', '/tienda/{slug:.+}', [Site\TiendaController::class, 'legacy']);
    $r->add('GET', '/diario', [Site\DiarioController::class, 'index']);
    $r->add('GET', '/diario/{slug}', [Site\DiarioController::class, 'show']);
    $r->add('GET|POST', '/reservar', [Site\ReservarController::class, 'index']);
    $r->add('GET', '/politica-de-privacidad', [Site\LegalController::class, 'privacidad']);
    $r->add('GET', '/terminos', [Site\LegalController::class, 'terminos']);
    $r->add('GET', '/sitemap.xml', [Site\SitemapController::class, 'sitemap']);
    $r->add('GET', '/robots.txt', [Site\SitemapController::class, 'robots']);
    $r->add('GET', '/wa', [Site\WhatsappController::class, 'go']);
};
