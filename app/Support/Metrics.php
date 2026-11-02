<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Contadores propios y anónimos por día (§13, alternativa simple a GA4 Data API).
 * No guarda IP, cookies ni identificadores.
 */
final class Metrics
{
    public const ALLOWED = ['visits', 'whatsapp_clicks', 'booking_starts', 'booking_submits', 'quotes', 'guide_signups', 'checkouts'];

    public static function bump(string $metric, int $by = 1): void
    {
        if (!in_array($metric, self::ALLOWED, true)) {
            return;
        }
        try {
            $day = local_time(now_utc())?->format('Y-m-d') ?? gmdate('Y-m-d');
            Db::run(
                'INSERT INTO metrics_daily (day, metric, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = value + VALUES(value)',
                [$day, $metric, $by],
            );
        } catch (\Throwable) {
            // la métrica nunca rompe una petición
        }
    }

    /** Visita anónima a una página pública (sin bots, sin cookies ni IP). */
    public static function visit(): void
    {
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        if ($ua === '' || preg_match('/bot|crawl|spider|slurp|preview|facebookexternalhit|curl|wget|python|headless|lighthouse|playwright/i', $ua)) {
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || PHP_SAPI === 'cli') {
            return;
        }
        self::bump('visits');
    }

    /** @return array<string,int> */
    public static function totals(string $fromDay, string $toDay): array
    {
        $out = array_fill_keys(self::ALLOWED, 0);
        foreach (Db::all('SELECT metric, SUM(value) AS v FROM metrics_daily WHERE day BETWEEN ? AND ? GROUP BY metric', [$fromDay, $toDay]) as $r) {
            $out[$r['metric']] = (int) $r['v'];
        }
        return $out;
    }
}
