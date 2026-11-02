<?php
/**
 * Versión imprimible del corte (para «Guardar como PDF»). Sin layout del panel.
 * @var array $p @var array $x
 */
use App\Domain\Commission\CommissionService;
use App\Domain\Commission\RateService;
use App\Domain\Commission\ReportService;

$fmt = static fn(int $n): string => money($n, false);
$total = $x['servicesAmount'] + $x['ordersAmount'];
?><!doctype html>
<html lang="es-CO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Corte de comisiones · <?= e(ReportService::periodName($p)) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/tokens.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/panel-comisiones.css')) ?>">
</head>
<body class="print-doc">
<header class="pd-head">
  <div>
    <p class="pd-lbl">Make Up by Fletcher · Liquidación de comisiones</p>
    <h1>Corte de <?= e(ReportService::periodName($p)) ?></h1>
  </div>
  <div class="pd-meta">
    <p>Estado: <strong><?= e(CommissionService::STATUS_LABELS[$p['status']] ?? $p['status']) ?></strong></p>
    <p>Generado: <?= e(fecha_corta(local_time(now_utc())) . ' ' . local_time(now_utc())->format('G:i')) ?></p>
    <p class="no-print"><a href="/panel/comisiones?periodo=<?= (int) $p['id'] ?>">Volver al panel</a></p>
  </div>
</header>

<table class="pd-sum">
  <tr><th>Servicios realizados</th><td><?= e($fmt($x['servicesBase'])) ?></td><th>Comisión servicios</th><td><?= e($fmt($x['servicesAmount'])) ?></td></tr>
  <tr><th>Ventas en línea</th><td><?= e($fmt($x['ordersBase'])) ?></td><th>Comisión ventas</th><td><?= e($fmt($x['ordersAmount'])) ?></td></tr>
  <tr class="tot"><th colspan="3">Comisión total del corte (COP)</th><td><?= e($fmt($total)) ?></td></tr>
</table>

<h2>Servicios</h2>
<table class="pd-tbl">
  <thead><tr><th>Código</th><th>Servicio</th><th>Fecha</th><th>Origen</th><th class="n">Valor</th><th class="n">Tasa</th><th class="n">Comisión</th></tr></thead>
  <tbody>
  <?php foreach ($x['services'] as $l): ?>
  <tr><td><?= e($l['code']) ?></td><td><?= e(ReportService::serviceShort((string) $l['service_code'], (int) $l['people'], $l['event_type'])) ?><?= $l['note'] ? ' · ' . e($l['note']) : '' ?></td><td><?= e(local_time($l['starts_at'])?->format('Y-m-d') ?? '') ?></td><td><?= e(ReportService::originLabel((string) $l['source'])) ?></td><td class="n"><?= e($fmt((int) $l['base'])) ?></td><td class="n"><?= e(RateService::percent((int) $l['rate_bp'])) ?></td><td class="n"><?= e($fmt((int) $l['amount'])) ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$x['services']): ?><tr><td colspan="7">Sin servicios en este corte.</td></tr><?php endif; ?>
  </tbody>
</table>

<h2>Ventas en línea</h2>
<table class="pd-tbl">
  <thead><tr><th>Pedido</th><th>Producto</th><th>Pago</th><th>Referencia Wompi</th><th class="n">Valor</th><th class="n">Tasa</th><th class="n">Comisión</th></tr></thead>
  <tbody>
  <?php foreach ($x['orders'] as $l): ?>
  <tr><td><?= e($l['reference']) ?></td><td><?= e($l['products'] ?? '') ?></td><td><?= e($l['paid_at'] ? local_time($l['paid_at'])->format('Y-m-d') : '') ?></td><td><?= e($l['wompi_transaction_id'] ?? '') ?></td><td class="n"><?= e($fmt((int) $l['base'])) ?></td><td class="n"><?= e(RateService::percent((int) $l['rate_bp'])) ?></td><td class="n"><?= e($fmt((int) $l['amount'])) ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$x['orders']): ?><tr><td colspan="7">Sin ventas en línea en este corte.</td></tr><?php endif; ?>
  </tbody>
</table>

<h2>Aprobaciones</h2>
<table class="pd-sum">
  <?php foreach ($x['approvers'] as $a): ?>
  <tr><th><?= e($a['name']) ?></th><td colspan="3"><?= $a['at'] ? 'Aprobó el ' . e(local_time($a['at'])->format('Y-m-d H:i')) : 'Pendiente' ?></td></tr>
  <?php endforeach; ?>
  <?php if ($p['payment_note']): ?><tr><th>Pago</th><td colspan="3"><?= e($p['payment_note']) ?></td></tr><?php endif; ?>
</table>
<?php if ($p['snapshot_hash']): ?>
<p class="pd-hash">Huella SHA-256 de las líneas al cerrar: <?= e((string) $p['snapshot_hash']) ?> · <?= $x['snapshotOk'] ? 'verificada' : 'NO coincide' ?></p>
<?php endif; ?>
<p class="pd-foot">Cálculo: comisión = parte entera de (valor × tasa en puntos básicos ÷ 10.000). Cada reserva y pedido guarda la tasa vigente al crearse.</p>
</body>
</html>
