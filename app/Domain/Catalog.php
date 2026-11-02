<?php
declare(strict_types=1);

namespace App\Domain;

use App\Support\Db;

/**
 * Precios vigentes de servicios y cursos: BD si está disponible (editable en Ajustes),
 * si no, config/catalog.php. Las vistas nunca escriben precios a mano.
 */
final class Catalog
{
    /** @var array<string,array<string,mixed>>|null */
    private static ?array $services = null;

    /** @return array<string,array{name:string,short:string,price:int,includes:list<string>}> */
    public static function packs(): array
    {
        return array_intersect_key(self::all(), array_flip(['pack1', 'pack2', 'pack3']));
    }

    /** @return array<string,array{name:string,price:int}> */
    public static function courses(): array
    {
        return array_intersect_key(self::all(), array_flip(['curso-auto', 'curso-pro']));
    }

    public static function price(string $code): int
    {
        return (int) (self::all()[$code]['price'] ?? 0);
    }

    /** @return array<string,array<string,mixed>> */
    public static function all(): array
    {
        if (self::$services !== null) {
            return self::$services;
        }
        $base = (array) config('catalog.services', []) + (array) config('catalog.courses', []);
        try {
            foreach (Db::all('SELECT code, name, price, active FROM services') as $row) {
                if (isset($base[$row['code']])) {
                    $base[$row['code']]['price'] = (int) $row['price'];
                    $base[$row['code']]['active'] = (bool) $row['active'];
                    $base[$row['code']]['db_name'] = $row['name'];
                }
            }
        } catch (\Throwable) {
            // Sin BD: precios del catálogo 2026
        }
        return self::$services = $base;
    }

    public static function reset(): void
    {
        self::$services = null;
    }
}
