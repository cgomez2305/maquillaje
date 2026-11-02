<?php
declare(strict_types=1);

/**
 * Diario 3:00: genera /sitemap.xml con el mismo controlador del sitio, valida el XML y guarda una
 * copia en storage/cache/sitemap.xml (útil para revisar qué se está enviando a Search Console).
 *   php cron/sitemap.php
 */

require_once __DIR__ . '/_lock.php';

use App\Controllers\Site\SitemapController;
use App\Http\Request;

/** @return int número de URL o -1 si el XML no es válido */
function cron_build_sitemap(): int
{
    $req = new Request('GET', '/sitemap.xml', [], [], ['HTTPS' => 'on', 'REQUEST_METHOD' => 'GET'], []);
    $xml = (new SitemapController())->sitemap($req)->body();
    $prev = libxml_use_internal_errors(true);
    $doc = simplexml_load_string($xml);
    libxml_use_internal_errors($prev);
    if ($doc === false) {
        return -1;
    }
    @file_put_contents(BASE_PATH . '/storage/cache/sitemap.xml', $xml, LOCK_EX);
    return count($doc->url ?? []);
}

if (cron_should_run()) {
    $lock = cron_lock('sitemap');
    if ($lock !== null) {
        $n = cron_build_sitemap();
        cron_out('sitemap', $n < 0 ? 'ERROR: XML no válido' : "urls={$n}");
        exit($n < 0 ? 1 : 0);
    }
}
