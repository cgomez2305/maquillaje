<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Support\Db;

/**
 * Productos de la tienda (tabla products). Solo sentencias preparadas.
 */
final class ProductRepository
{
    private const COLUMNS = 'id, slug, name, category, subcategory, service_code, price, stock, active, summary, description, image, sort';

    /** Producto activo y comprable por slug, o null. */
    public static function findActiveBySlug(string $slug): ?array
    {
        $row = Db::one('SELECT ' . self::COLUMNS . ' FROM products WHERE slug = ? AND active = 1 AND price > 0', [$slug]);
        return $row === null ? null : self::cast($row);
    }

    /** Producto por slug sin filtrar por estado (panel, pedidos). */
    public static function findBySlug(string $slug): ?array
    {
        $row = Db::one('SELECT ' . self::COLUMNS . ' FROM products WHERE slug = ?', [$slug]);
        return $row === null ? null : self::cast($row);
    }

    public static function find(int $id): ?array
    {
        $row = Db::one('SELECT ' . self::COLUMNS . ' FROM products WHERE id = ?', [$id]);
        return $row === null ? null : self::cast($row);
    }

    /** @return list<array<string,mixed>> productos activos */
    public static function active(): array
    {
        return array_map([self::class, 'cast'], Db::all('SELECT ' . self::COLUMNS . ' FROM products WHERE active = 1 AND price > 0 ORDER BY sort, id'));
    }

    /**
     * @param bool $onlyActive  false incluye los de precio pendiente (se muestran con «[Precio]» y se piden por WhatsApp)
     * @return list<array<string,mixed>>
     */
    public static function bySubcategory(string $subcategory, bool $onlyActive = true): array
    {
        $sql = 'SELECT ' . self::COLUMNS . ' FROM products WHERE subcategory = ?' . ($onlyActive ? ' AND active = 1 AND price > 0' : '') . ' ORDER BY sort, id';
        return array_map([self::class, 'cast'], Db::all($sql, [$subcategory]));
    }

    /** @return list<array<string,mixed>> otros productos activos de la misma categoría */
    public static function related(array $product, int $limit = 3): array
    {
        $rows = Db::all(
            'SELECT ' . self::COLUMNS . ' FROM products WHERE category = ? AND id <> ? AND active = 1 AND price > 0 ORDER BY sort, id LIMIT ' . max(1, min(12, $limit)),
            [$product['category'], $product['id']],
        );
        return array_map([self::class, 'cast'], $rows);
    }

    /** @return list<string> slugs activos (sitemap) */
    public static function activeSlugs(): array
    {
        return array_map('strval', Db::run('SELECT slug FROM products WHERE active = 1 AND price > 0 ORDER BY sort, id')->fetchAll(\PDO::FETCH_COLUMN));
    }

    /** ¿Está disponible para comprar en línea? */
    public static function isPurchasable(array $p): bool
    {
        return $p['active'] && $p['price'] > 0 && ($p['stock'] === null || $p['stock'] > 0);
    }

    private static function cast(array $r): array
    {
        $r['id'] = (int) $r['id'];
        $r['price'] = (int) $r['price'];
        $r['stock'] = $r['stock'] === null ? null : (int) $r['stock'];
        $r['active'] = (bool) $r['active'];
        $r['sort'] = (int) $r['sort'];
        return $r;
    }
}
