<?php
declare(strict_types=1);

namespace App\Controllers\Panel;

use App\Controllers\Controller;
use App\Domain\Audit\AuditLog;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Http\Session;
use App\Panel\Auth;
use App\Support\Db;
use App\Support\HtmlSanitizer;

/**
 * Administración básica del Diario (fase 7): borradores, publicación y edición.
 * El HTML se sanea al guardar con lista blanca (§14.3). Los precios se escriben como
 * {{precio:pack3}} para que sigan a los de Ajustes.
 */
final class DiarioController extends Controller
{
    public const CATEGORIES = ['Novias', 'Quinceañeras', 'Grados', 'Tips de piel', 'Cursos', 'Servicios'];
    public const LINKS = [
        '/maquillaje-de-novia-bucaramanga' => 'Novias y quinceañeras',
        '/precios-maquillaje-bucaramanga' => 'Servicios y precios',
        '/cursos-de-maquillaje-bucaramanga' => 'Cursos',
        '/estudio-maquillaje-floridablanca' => 'Estudio',
        '/tienda' => 'Tienda',
    ];

    public function index(Request $request): Response
    {
        $posts = Db::all('SELECT id, slug, title, category, status, published_at, updated_at, featured FROM posts ORDER BY status = \'publicado\' DESC, COALESCE(published_at, updated_at) DESC');
        return $this->view('panel/diario', ['title' => 'Diario', 'active' => 'diario', 'posts' => $posts], 'panel');
    }

    public function edit(Request $request): Response
    {
        $id = $request->params['id'] ?? null;
        $post = $id !== null ? Db::one('SELECT * FROM posts WHERE id = ?', [(int) $id]) : null;
        if ($id !== null && $post === null) {
            throw new HttpException(404);
        }
        $errors = [];
        $old = $post ?? ['status' => 'borrador', 'featured' => 0, 'category' => 'Novias', 'faq' => null];
        $old['faq_text'] = $this->faqToText($post['faq'] ?? null);

        if ($request->isPost()) {
            [$errors, $data] = $this->validate($request, $post);
            $old = array_merge($old, $data);
            $old['faq_text'] = $request->str('faq_text', 6000);
            if ($errors === []) {
                $saved = $this->save($post, $data);
                Session::flash('notice', $data['status'] === 'publicado' ? 'Artículo publicado.' : 'Borrador guardado.');
                return Response::redirect('/panel/diario/' . $saved, 303);
            }
        }
        return $this->view('panel/diario-editar', [
            'title' => $post ? 'Editar artículo' : 'Nuevo artículo', 'active' => 'diario',
            'p' => $old, 'isNew' => $post === null, 'errors' => $errors,
            'categories' => self::CATEGORIES, 'links' => self::LINKS, 'covers' => $this->covers(),
        ], 'panel', $errors ? 422 : 200);
    }

    /** @return array{0:array<string,string>,1:array<string,mixed>} */
    private function validate(Request $r, ?array $post): array
    {
        $e = [];
        $d = [];
        $d['title'] = $r->str('title', 200);
        if (mb_strlen($d['title']) < 10) {
            $e['title'] = 'El título debe tener al menos 10 caracteres.';
        }
        $d['seo_title'] = $r->str('seo_title', 70) ?: null;
        if ($d['seo_title'] !== null && mb_strlen($d['seo_title']) > 60) {
            $e['seo_title'] = 'El título SEO debe tener 60 caracteres o menos.';
        }
        $slug = HtmlSanitizer::slug($r->str('slug', 160) ?: $d['title']);
        if ($slug === '' || strlen($slug) > 160) {
            $e['slug'] = 'Revisa la dirección (solo letras, números y guiones).';
        } elseif (Db::value('SELECT id FROM posts WHERE slug = ? AND id <> ?', [$slug, (int) ($post['id'] ?? 0)])) {
            $e['slug'] = 'Ya existe un artículo con esa dirección.';
        }
        $d['slug'] = $slug;
        $d['meta_description'] = $r->str('meta_description', 160);
        if (mb_strlen($d['meta_description']) < 50 || mb_strlen($d['meta_description']) > 155) {
            $e['meta_description'] = 'La meta descripción debe tener entre 50 y 155 caracteres.';
        }
        $d['category'] = in_array($r->str('category', 40), self::CATEGORIES, true) ? $r->str('category', 40) : '';
        if ($d['category'] === '') {
            $e['category'] = 'Elige una categoría.';
        }
        $d['kicker'] = $r->str('kicker', 60) ?: null;
        $d['target_keyword'] = $r->str('target_keyword', 120);
        if (mb_strlen($d['target_keyword']) < 3) {
            $e['target_keyword'] = 'Escribe la keyword principal.';
        }
        $d['excerpt'] = $r->str('excerpt', 300);
        if (mb_strlen($d['excerpt']) < 30) {
            $e['excerpt'] = 'El extracto debe tener al menos 30 caracteres.';
        }
        $rawBody = is_string($r->post['body_html'] ?? null) ? mb_substr((string) $r->post['body_html'], 0, 200000) : '';
        $d['body_html'] = HtmlSanitizer::clean($rawBody);
        if (mb_strlen(strip_tags($d['body_html'])) < 200) {
            $e['body_html'] = 'El cuerpo del artículo es demasiado corto.';
        }
        [$faq, $faqErr] = $this->parseFaq($r->str('faq_text', 6000));
        if ($faqErr !== null) {
            $e['faq_text'] = $faqErr;
        }
        $d['faq'] = $faq === [] ? null : json_encode($faq, JSON_UNESCAPED_UNICODE);
        $cover = $r->str('cover', 255);
        $d['cover'] = array_key_exists($cover, $this->covers()) ? $cover : null;
        $links = $r->str('links_to', 160);
        $d['links_to'] = array_key_exists($links, self::LINKS) ? $links : null;
        $minutes = $r->int('reading_minutes', 0);
        $d['reading_minutes'] = $minutes > 0 && $minutes < 120 ? $minutes : max(1, (int) ceil(str_word_count(strip_tags($d['body_html'])) / 200));
        $d['featured'] = $r->input('featured') === '1' ? 1 : 0;
        $d['status'] = $r->str('status', 12) === 'publicado' ? 'publicado' : 'borrador';
        return [$e, $d];
    }

    private function save(?array $post, array $d): int
    {
        $now = now_utc();
        $publishedAt = $post['published_at'] ?? null;
        if ($d['status'] === 'publicado' && $publishedAt === null) {
            $publishedAt = $now;
        }
        $cols = ['slug', 'title', 'seo_title', 'meta_description', 'category', 'kicker', 'target_keyword', 'excerpt', 'body_html', 'faq', 'cover', 'reading_minutes', 'links_to', 'featured', 'status'];
        $values = array_map(static fn(string $c): mixed => $d[$c], $cols);
        return Db::transaction(static function () use ($post, $cols, $values, $publishedAt, $now, $d): int {
            if ($d['featured']) {
                Db::run('UPDATE posts SET featured = 0 WHERE featured = 1');
            }
            if ($post === null) {
                $id = Db::insert(
                    'INSERT INTO posts (' . implode(', ', $cols) . ', published_at, updated_at) VALUES (' . implode(', ', array_fill(0, count($cols) + 2, '?')) . ')',
                    [...$values, $publishedAt, $now],
                );
                AuditLog::record(Auth::id(), 'articulo_creado', 'post:' . $id, ['estado' => $d['status'], 'slug' => $d['slug']]);
                return $id;
            }
            Db::run(
                'UPDATE posts SET ' . implode(', ', array_map(static fn(string $c): string => "{$c} = ?", $cols)) . ', published_at = ?, updated_at = ? WHERE id = ?',
                [...$values, $publishedAt, $now, (int) $post['id']],
            );
            AuditLog::record(Auth::id(), 'articulo_editado', 'post:' . $post['id'], ['estado' => $d['status'], 'slug' => $d['slug']]);
            return (int) $post['id'];
        });
    }

    /**
     * FAQ en texto: bloques separados por línea en blanco, cada uno «P: pregunta» y «R: respuesta».
     * @return array{0:list<array{q:string,a:string}>,1:?string}
     */
    private function parseFaq(string $text): array
    {
        $out = [];
        foreach (preg_split('/\R\s*\R/', trim($text)) ?: [] as $block) {
            if (trim($block) === '') {
                continue;
            }
            if (!preg_match('/^\s*P:\s*(.+?)\s*\R\s*R:\s*(.+)$/su', $block, $m)) {
                return [[], 'Cada pregunta debe ir como «P: …» y en la línea siguiente «R: …», separadas por una línea en blanco.'];
            }
            $out[] = ['q' => mb_substr(trim(strip_tags($m[1])), 0, 200), 'a' => mb_substr(trim(strip_tags($m[2])), 0, 1200)];
        }
        return [$out, null];
    }

    private function faqToText(?string $json): string
    {
        $items = $json ? (json_decode($json, true) ?: []) : [];
        return implode("\n\n", array_map(static fn(array $i): string => 'P: ' . $i['q'] . "\nR: " . $i['a'], $items));
    }

    /** @return array<string,string> fotos disponibles para la portada */
    private function covers(): array
    {
        $file = BASE_PATH . '/public/assets/img/manifest.json';
        $m = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
        $out = [];
        foreach (array_keys($m) as $k) {
            if (str_starts_with($k, 'fotos/')) {
                $out[$k] = substr($k, 6);
            }
        }
        return $out;
    }
}
