<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Support\Db;

/** Artículos del diario. El sitio público solo ve los publicados. */
final class PostRepository
{
    private const COLS = 'id, slug, title, seo_title, meta_description, category, kicker, target_keyword, excerpt, cover, reading_minutes, links_to, featured, status, published_at, updated_at';

    /** @return list<array<string,mixed>> publicados, el destacado primero y luego del más reciente al más antiguo */
    public static function published(): array
    {
        return Db::all(
            'SELECT ' . self::COLS . " FROM posts WHERE status = 'publicado' AND published_at <= UTC_TIMESTAMP()
             ORDER BY featured DESC, published_at DESC, id DESC",
        );
    }

    /** @return array<string,mixed>|null */
    public static function findPublished(string $slug): ?array
    {
        return Db::one(
            'SELECT ' . self::COLS . ", body_html, faq FROM posts
             WHERE slug = ? AND status = 'publicado' AND published_at <= UTC_TIMESTAMP()",
            [$slug],
        );
    }

    /**
     * Relacionados: misma categoría primero, luego los más recientes.
     * @return list<array<string,mixed>>
     */
    public static function related(array $post, int $limit = 3): array
    {
        return Db::all(
            'SELECT ' . self::COLS . " FROM posts
             WHERE status = 'publicado' AND published_at <= UTC_TIMESTAMP() AND id <> ?
             ORDER BY (category = ?) DESC, published_at DESC LIMIT " . max(1, min(12, $limit)),
            [(int) $post['id'], (string) $post['category']],
        );
    }

    /** @return list<array{title:string,category:string}> guías en preparación (solo título y tema) */
    public static function upcoming(int $limit = 9): array
    {
        return Db::all(
            "SELECT title, category FROM posts WHERE status = 'borrador' ORDER BY id LIMIT " . max(1, min(20, $limit)),
        );
    }
}
