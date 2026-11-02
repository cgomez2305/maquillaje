<?php
declare(strict_types=1);

/**
 * Mensual (§14.5): anonimiza las clientas sin actividad en más de 3 años (sin reservas recientes
 * ni futuras). Sus reservas, pedidos y comisiones se conservan.
 *   php cron/retencion.php [--simular]
 */

require_once __DIR__ . '/_lock.php';

use App\Domain\Clients\AnonymizeService;

if (cron_should_run()) {
    $lock = cron_lock('retencion');
    if ($lock !== null) {
        $ids = AnonymizeService::inactiveSince(3);
        if (in_array('--simular', $argv ?? [], true)) {
            cron_out('retencion', 'simulación: ' . count($ids) . ' clientas por anonimizar');
            exit(0);
        }
        $n = 0;
        foreach ($ids as $id) {
            if (AnonymizeService::anonymize($id, null, 'Retención: más de 3 años sin actividad (cron)')) {
                $n++;
            }
        }
        cron_out('retencion', "anonimizadas={$n}");
    }
}
