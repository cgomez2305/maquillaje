<?php
declare(strict_types=1);

/**
 * Cierre mensual (§9.5). Día 1 a las 00:10 (hora de Bogotá):
 *   10 0 1 * *  php /ruta/cron/cierre-periodo.php
 * Pasa a «en_aprobacion» los periodos abiertos de meses anteriores, abre el del mes en curso
 * y avisa a la dueña y al socio. Idempotente: se puede ejecutar varias veces.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Domain\Audit\AuditLog;
use App\Domain\Commission\CommissionService;
use App\Domain\Commission\ReportService;
use App\Domain\Notifications\Mailer;
use App\Support\Db;

$moved = CommissionService::startApproval();
$notified = 0;

// Avisar de cada periodo en aprobación sin las dos firmas (el correo es idempotente por periodo)
foreach (Db::all("SELECT * FROM commission_periods WHERE status = 'en_aprobacion' ORDER BY year, month") as $p) {
    $data = [
        'periodName' => ReportService::periodName($p),
        'total' => (int) $p['total_commission'],
        'base' => (int) $p['total_base'],
        'lines' => (int) Db::value('SELECT COUNT(*) FROM commission_lines WHERE period_id = ?', [(int) $p['id']]),
        'url' => url('/panel/comisiones?periodo=' . $p['id']),
    ];
    foreach (['owner' => Mailer::owner(), 'partner' => Mailer::partner()] as $role => $to) {
        if ($to !== '' && Mailer::send($to, 'Corte de ' . $data['periodName'] . ' listo para aprobar', 'corte-listo', $data, 'corte-listo-' . $p['id'] . '-' . $role)) {
            $notified++;
        }
    }
}

if ($moved !== []) {
    AuditLog::record(null, 'corte_en_aprobacion', 'periodos:' . implode(',', $moved), ['cron' => 'cierre-periodo']);
}
printf("Periodos pasados a aprobación: %d (%s). Correos enviados o ya registrados: %d\n", count($moved), implode(', ', $moved) ?: '—', $notified);
