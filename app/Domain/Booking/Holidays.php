<?php
declare(strict_types=1);

namespace App\Domain\Booking;

/**
 * Festivos de Colombia (Ley 51 de 1983, «Ley Emiliani»): fijos, trasladables al lunes
 * siguiente y los que dependen de la Pascua.
 */
final class Holidays
{
    /** @var array<int,array<string,string>> */
    private static array $cache = [];

    /** @return array<string,string> fecha Y-m-d => nombre */
    public static function forYear(int $year): array
    {
        if (isset(self::$cache[$year])) {
            return self::$cache[$year];
        }
        $d = static fn(int $m, int $day): \DateTimeImmutable => new \DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $m, $day));
        $monday = static function (\DateTimeImmutable $x): \DateTimeImmutable {
            $w = (int) $x->format('N');
            return $w === 1 ? $x : $x->modify('next monday');
        };
        $easter = (new \DateTimeImmutable("{$year}-03-21"))->modify('+' . easter_days($year) . ' days');

        $list = [
            [$d(1, 1), 'Año Nuevo'],
            [$d(5, 1), 'Día del Trabajo'],
            [$d(7, 20), 'Día de la Independencia'],
            [$d(8, 7), 'Batalla de Boyacá'],
            [$d(12, 8), 'Inmaculada Concepción'],
            [$d(12, 25), 'Navidad'],
            [$monday($d(1, 6)), 'Reyes Magos'],
            [$monday($d(3, 19)), 'San José'],
            [$monday($d(6, 29)), 'San Pedro y San Pablo'],
            [$monday($d(8, 15)), 'Asunción de la Virgen'],
            [$monday($d(10, 12)), 'Día de la Raza'],
            [$monday($d(11, 1)), 'Todos los Santos'],
            [$monday($d(11, 11)), 'Independencia de Cartagena'],
            [$easter->modify('-3 days'), 'Jueves Santo'],
            [$easter->modify('-2 days'), 'Viernes Santo'],
            [$monday($easter->modify('+39 days')), 'Ascensión del Señor'],
            [$monday($easter->modify('+60 days')), 'Corpus Christi'],
            [$monday($easter->modify('+68 days')), 'Sagrado Corazón'],
        ];
        $out = [];
        foreach ($list as [$date, $name]) {
            $out[$date->format('Y-m-d')] = $name;
        }
        ksort($out);
        return self::$cache[$year] = $out;
    }

    public static function name(string $ymd): ?string
    {
        return self::forYear((int) substr($ymd, 0, 4))[$ymd] ?? null;
    }
}
