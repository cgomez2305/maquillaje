<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Domain\Catalog;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\PostRepository;
use App\Support\HtmlSanitizer;
use App\Support\Schema;

/**
 * Diario (blog) y guía gratuita para novias con doble opt-in (§7, §12.5).
 */
final class DiarioController extends Controller
{
    /** Orden de los temas en los chips (los que no tengan artículos publicados no se muestran). */
    private const TOPICS = ['Novias', 'Quinceañeras', 'Grados', 'Tips de piel', 'Cursos', 'Servicios'];

    public function index(Request $request): Response
    {
        $posts = PostRepository::published();
        $featured = null;
        foreach ($posts as $i => $p) {
            if ((int) $p['featured'] === 1) {
                $featured = $p;
                unset($posts[$i]);
                break;
            }
        }
        if ($featured === null && $posts !== []) {
            $featured = array_shift($posts);
        }
        $posts = array_values($posts);
        $present = array_unique(array_column($posts, 'category'));
        $topics = array_values(array_filter(self::TOPICS, static fn(string $t): bool => in_array($t, $present, true)));

        return $this->page('diario', 'site/diario', [
            'featured' => $featured,
            'posts' => $posts,
            'topics' => $topics,
            'upcoming' => $posts === [] ? PostRepository::upcoming() : [],
            'css' => ['pages/diario.css'],
            'js' => ['diario.js'],
            'crumbs' => [['Inicio', '/'], ['Diario', null]],
            'waOrigin' => 'diario',
        ]);
    }

    public function show(Request $request): Response
    {
        $post = PostRepository::findPublished($request->param('slug'));
        if ($post === null) {
            throw new HttpException(404);
        }
        $path = '/diario/' . $post['slug'];
        $body = self::fillPrices(HtmlSanitizer::clean((string) $post['body_html']));
        $faq = [];
        foreach (json_decode((string) ($post['faq'] ?? '[]'), true) ?: [] as $item) {
            if (isset($item['q'], $item['a']) && is_string($item['q']) && is_string($item['a'])) {
                $faq[] = ['q' => $item['q'], 'a' => self::fillPrices($item['a'], false)];
            }
        }
        $toc = HtmlSanitizer::headings($body);
        if ($faq !== []) {
            $toc[] = ['id' => 'preguntas', 'text' => 'Preguntas frecuentes'];
        }

        $coverUrl = self::coverUrl($post['cover'] ?? null);
        $title = (string) ($post['seo_title'] ?: $post['title']);
        if (mb_strlen($title) > 60) {
            $title = rtrim(mb_substr($title, 0, 59)) . '…';
        } elseif (!$post['seo_title'] && mb_strlen($title . ' · Fletcher') <= 60) {
            $title .= ' · Fletcher';
        }

        $schema = [Schema::blogPosting($post, $path, $coverUrl)];
        if ($faq !== []) {
            $schema[] = Schema::faq($faq);
        }

        return $this->page('diario', 'site/articulo', [
            'post' => $post,
            'body' => $body,
            'faq' => $faq,
            'toc' => $toc,
            'related' => PostRepository::related($post),
            'published' => local_time($post['published_at']),
            'shareUrl' => url($path),
            'meta' => [
                'title' => $title,
                'description' => (string) $post['meta_description'],
                'canonical' => $path,
                'og_type' => 'article',
                'og_image' => $coverUrl !== null ? url($coverUrl) : null,
            ],
            'path' => $path,
            'crumbs' => [['Inicio', '/'], ['Diario', '/diario'], [(string) $post['category'], null]],
            'schema' => $schema,
            'css' => ['pages/articulo.css'],
            'js' => ['diario.js'],
            'preload' => $post['cover'] ? ['key' => (string) $post['cover'], 'sizes' => '(max-width: 1320px) 100vw, 1240px'] : null,
            'waOrigin' => 'diario-' . substr((string) $post['slug'], 0, 40),
        ]);
    }

    /**
     * Token de 43 caracteres [a-z0-9] (~222 bits). Solo minúsculas porque el Kernel redirige
     * las URLs públicas con mayúsculas a su versión en minúscula.
     */
    private static function lowerToken(): string
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $out = '';
        while (strlen($out) < 43) {
            foreach (str_split(random_bytes(64)) as $byte) {
                $n = ord($byte);
                if ($n < 252) { // 252 = 7 × 36: sin sesgo de módulo
                    $out .= $alphabet[$n % 36];
                    if (strlen($out) === 43) {
                        break;
                    }
                }
            }
        }
        return $out;
    }

    /** Reemplaza {{precio:codigo}} por el precio vigente. $html=false para texto plano. */
    private static function fillPrices(string $text, bool $html = true): string
    {
        return (string) preg_replace_callback('/\{\{precio:([a-z0-9-]{2,30})\}\}/', static function (array $m) use ($html): string {
            $price = Catalog::price($m[1]);
            $out = $price > 0 ? money($price) : '[precio pendiente]';
            return $html ? e($out) : $out;
        }, $text);
    }

    /** Ruta pública del JPG más grande de la portada (Open Graph y JSON-LD). */
    private static function coverUrl(?string $key): ?string
    {
        if ($key === null || $key === '') {
            return null;
        }
        $file = BASE_PATH . '/public/assets/img/manifest.json';
        $m = is_file($file) ? (json_decode((string) file_get_contents($file), true)[$key] ?? null) : null;
        if ($m === null) {
            return null;
        }
        return '/assets/img/' . $key . '-' . max($m['widths']) . '.' . $m['ext'];
    }
}
