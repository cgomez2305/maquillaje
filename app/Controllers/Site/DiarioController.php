<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Domain\Catalog;
use App\Domain\Leads\LeadService;
use App\Domain\Notifications\Mailer;
use App\Http\FormGuard;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Session;
use App\Repositories\PostRepository;
use App\Support\Db;
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
            'guide' => [
                'ok' => Session::flash('guia_ok'),
                'errors' => Session::flash('guia_errors') ?? [],
                'old' => Session::flash('guia_old') ?? [],
            ],
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

    /** POST /diario/guia-novias: registra el lead y envía el correo de confirmación (doble opt-in). */
    public function guia(Request $request): Response
    {
        $back = Response::redirect('/diario#guia', 303);
        $name = $request->str('nombre', 120);
        $email = mb_strtolower($request->str('correo', 190));
        $date = $request->str('fecha_boda', 10);
        $old = ['nombre' => $name, 'correo' => $email, 'fecha_boda' => $date];

        $spam = FormGuard::check($request, 'guia');
        if ($spam !== null) {
            Session::flash('guia_errors', ['form' => $spam]);
            Session::flash('guia_old', $old);
            return $back;
        }

        $errors = [];
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['correo'] = 'Escribe un correo válido.';
        }
        $wedding = \DateTimeImmutable::createFromFormat('!Y-m-d', $date, new \DateTimeZone(config('app.timezone')));
        $today = new \DateTimeImmutable('today', new \DateTimeZone(config('app.timezone')));
        if ($wedding === false || $wedding->format('Y-m-d') !== $date) {
            $errors['fecha_boda'] = 'Indica la fecha de tu boda.';
        } elseif ($wedding < $today || $wedding > $today->modify('+3 years')) {
            $errors['fecha_boda'] = 'La fecha debe ser a partir de hoy y en los próximos tres años.';
        }
        if (($request->post['acepto'] ?? '') !== '1') {
            $errors['acepto'] = 'Necesitamos tu autorización para enviarte la guía.';
        }
        if ($errors !== []) {
            Session::flash('guia_errors', $errors);
            Session::flash('guia_old', $old);
            return $back;
        }

        $token = self::lowerToken();
        try {
            $lead = LeadService::create('guia_novias', [
                'name' => $name !== '' ? $name : null,
                'email' => $email,
                'payload' => ['wedding_date' => $date],
                'confirm_token_hash' => hash('sha256', $token),
                'consent_at' => now_utc(),
                'origin' => 'diario-guia',
            ]);
            Mailer::send($email, 'Confirma tu correo para recibir la guía de novias', 'guia-confirmar', [
                'name' => $name,
                'link' => url('/diario/confirmar/' . $token),
            ], 'guia-confirmar-' . $lead['id']);
        } catch (\Throwable $e) {
            app_log('error', 'Alta de guía fallida: ' . $e->getMessage());
            Session::flash('guia_errors', ['form' => 'No pudimos registrar tu correo. Inténtalo de nuevo en unos minutos.']);
            Session::flash('guia_old', $old);
            return $back;
        }

        Session::flash('guia_ok', $email);
        return $back;
    }

    /** GET /diario/confirmar/{token}: confirma una sola vez y envía la guía. */
    public function confirmar(Request $request): Response
    {
        $hash = hash('sha256', $request->param('token'));
        $lead = Db::one("SELECT id, name, email, confirmed_at FROM leads WHERE kind = 'guia_novias' AND confirm_token_hash = ?", [$hash]);
        if ($lead === null) {
            throw new HttpException(404);
        }
        $first = $lead['confirmed_at'] === null;
        if ($first) {
            Db::run('UPDATE leads SET confirmed_at = ? WHERE id = ? AND confirmed_at IS NULL', [now_utc(), (int) $lead['id']]);
            Mailer::send((string) $lead['email'], 'Tu guía de belleza para novias · Make Up by Fletcher', 'guia-novias', [
                'name' => (string) ($lead['name'] ?? ''),
                'posts' => array_slice(PostRepository::published(), 0, 4),
                'pack3' => Catalog::price('pack3'),
            ], 'guia-novias-' . $lead['id']);
        }

        return $this->page('diario', 'site/guia-confirmada', [
            'first' => $first,
            'meta' => [
                'title' => 'Correo confirmado · Guía para novias · Fletcher',
                'description' => 'Confirmaste tu correo para recibir la guía de belleza para novias de Make Up by Fletcher.',
                'canonical' => '/diario',
                'robots' => 'noindex, nofollow',
            ],
            'path' => '/diario',
            'crumbs' => [['Inicio', '/'], ['Diario', '/diario'], ['Guía para novias', null]],
            'css' => ['pages/diario.css'],
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
