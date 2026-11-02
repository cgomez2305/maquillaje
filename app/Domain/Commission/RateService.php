<?php
declare(strict_types=1);

namespace App\Domain\Commission;

use App\Support\Db;

/**
 * Tasas de comisión vigentes (§9.4): solo rige una tasa aprobada por la dueña y el socio,
 * con valid_from ya cumplido. Sin tasa aprobada, la tasa es 0.
 */
final class RateService
{
    public const CATEGORIES = ['service' => 'Servicios', 'course' => 'Cursos', 'product' => 'Tienda', 'gift' => 'Bonos de regalo'];

    public static function current(string $category, ?string $atUtc = null): int
    {
        $atUtc ??= now_utc();
        $bp = Db::value(
            'SELECT rate_bp FROM commission_rates
             WHERE category = ? AND valid_from <= ? AND approved_by_owner_at IS NOT NULL AND approved_by_partner_at IS NOT NULL
             ORDER BY valid_from DESC, id DESC LIMIT 1',
            [$category, $atUtc],
        );
        return $bp === null ? 0 : (int) $bp;
    }

    /** @return array<string,int> */
    public static function allCurrent(): array
    {
        $out = [];
        foreach (array_keys(self::CATEGORIES) as $c) {
            $out[$c] = self::current($c);
        }
        return $out;
    }

    public static function amount(int $base, int $rateBp): int
    {
        // §9.4: amount = intdiv(base * rate_bp, 10000); con signo para reembolsos
        return $base < 0 ? -intdiv(-$base * $rateBp, 10000) : intdiv($base * $rateBp, 10000);
    }

    public static function percent(int $bp): string
    {
        return rtrim(rtrim(number_format($bp / 100, 2, ',', '.'), '0'), ',') . ' %';
    }
}
