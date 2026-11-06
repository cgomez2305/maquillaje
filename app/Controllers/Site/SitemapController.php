<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Support\Db;

/** /sitemap.xml dinámico y /robots.txt (§12.4). */
final class SitemapController extends Controller
{
    private const PRIORITY = [
        'home' => '1.0', 'novias' => '0.9', 'servicios' => '0.9', 'cursos' => '0.8', 'estudio' => '0.8',
        'tienda' => '0.7', 'portafolio' => '0.7', 'diario' => '0.6', 'privacidad' => '0.2', 'terminos' => '0.2',
    ];

    public function sitemap(Request $request): Response
    {
        $urls = [];
        foreach ((array) config('seo', []) as $key => $page) {
            // Reservar arma un mensaje de WhatsApp y la tienda está en «próximamente»: no se indexan
            if (in_array($key, ['reservar', 'tienda'], true) || !isset($page['path'])) {
                continue;
            }
            $urls[] = ['key' => $key, 'loc' => url($page['path']), 'priority' => self::PRIORITY[$key] ?? '0.5', 'lastmod' => self::lastmod($key)];
        }

        try {
            $latest = null;
            foreach (Db::all("SELECT slug, COALESCE(updated_at, published_at) AS mod_at FROM posts WHERE status = 'publicado' AND published_at <= UTC_TIMESTAMP() ORDER BY published_at DESC") as $p) {
                $mod = $p['mod_at'] ? gmdate('Y-m-d', (int) strtotime($p['mod_at'] . ' UTC')) : null;
                $latest = max($latest ?? '', $mod ?? '') ?: null;
                $urls[] = ['loc' => url('/diario/' . $p['slug']), 'priority' => '0.6', 'lastmod' => $mod];
            }
            // El índice del diario cambia cuando cambia su artículo más reciente
            foreach ($urls as &$u) {
                if (($u['key'] ?? '') === 'diario' && $latest !== null) {
                    $u['lastmod'] = max((string) $u['lastmod'], $latest);
                }
            }
            unset($u);
        } catch (\Throwable $e) {
            app_log('warning', 'Sitemap sin BD: ' . $e->getMessage());
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</loc>'
                . ($u['lastmod'] ? '<lastmod>' . $u['lastmod'] . '</lastmod>' : '')
                . '<priority>' . $u['priority'] . '</priority></url>' . "\n";
        }
        $xml .= '</urlset>' . "\n";

        return Response::text($xml, 'application/xml')->header('Cache-Control', 'public, max-age=3600');
    }

    /** Vistas de cada página: su fecha de modificación es la de la página (para que Google priorice el rastreo). */
    private const VIEWS = [
        'home' => ['site/home', 'Site/HomeController'], 'novias' => ['site/novias', 'Site/NoviasController'],
        'servicios' => ['site/servicios', 'Site/ServiciosController'], 'cursos' => ['site/cursos', 'Site/CursosController'],
        'portafolio' => ['site/portafolio', 'Site/PortafolioController'], 'estudio' => ['site/estudio', 'Site/EstudioController'],
        'diario' => ['site/diario', 'Site/DiarioController'], 'privacidad' => ['site/legal-privacidad'], 'terminos' => ['site/legal-terminos'],
    ];

    private static function lastmod(string $key): ?string
    {
        $files = [];
        foreach (self::VIEWS[$key] ?? [] as $i => $f) {
            $files[] = BASE_PATH . ($i === 0 ? '/app/Views/' . $f . '.php' : '/app/Controllers/' . $f . '.php');
        }
        $times = array_filter(array_map(static fn(string $f): int|false => @filemtime($f), $files));
        return $times ? gmdate('Y-m-d', max($times)) : null;
    }

    public function robots(Request $request): Response
    {
        $lines = ['User-agent: *'];
        if (config('app.env') !== 'production') {
            $lines[] = 'Disallow: /';
        } else {
            foreach (['/wa'] as $p) {
                $lines[] = 'Disallow: ' . $p;
            }
            $lines[] = 'Allow: /';
        }
        $lines[] = '';
        $lines[] = 'Sitemap: ' . url('/sitemap.xml');
        return Response::text(implode("\n", $lines) . "\n")->header('Cache-Control', 'public, max-age=3600');
    }
}
