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
            $urls[] = ['loc' => url($page['path']), 'priority' => self::PRIORITY[$key] ?? '0.5', 'lastmod' => null];
        }

        try {
            foreach (Db::all("SELECT slug, COALESCE(updated_at, published_at) AS mod_at FROM posts WHERE status = 'publicado' AND published_at <= UTC_TIMESTAMP() ORDER BY published_at DESC") as $p) {
                $urls[] = ['loc' => url('/diario/' . $p['slug']), 'priority' => '0.6', 'lastmod' => $p['mod_at'] ? gmdate('Y-m-d', (int) strtotime($p['mod_at'] . ' UTC')) : null];
            }
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
