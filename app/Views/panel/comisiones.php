<?php
/**
 * Comisiones (Panel-Comisiones.dc.html).
 * @var array $p @var array $x @var list<array> $periods @var array<string,int> $rates
 * @var list<array> $pendingRates @var list<array> $scheduledRates @var string $role @var bool $canApprove @var string $today
 */
use App\Domain\Commission\CommissionService;
use App\Domain\Commission\RateService;
use App\Domain\Commission\ReportService;

$status = $p['status'];
$closed = in_array($status, ['cerrado', 'pagado'], true);
$fmt = static fn(int $n): string => money($n, false);
$day = static function (?string $utc): string {
    $t = local_time($utc);
    return $t ? (int) $t->format('j') . ' ' . mb_substr(mes_nombre((int) $t->format('n')), 0, 3) : '—';
};
$pill = ['abierto' => '', 'en_aprobacion' => 'warn', 'cerrado' => 'good', 'pagado' => 'good'][$status] ?? '';
$total = $x['servicesAmount'] + $x['ordersAmount'];
?>
<div class="top">
  <div class="stack gap-8"><p class="lbl">Liquidación</p><h1 class="d">Comisiones · <?= e(ReportService::periodName($p)) ?></h1></div>
  <div class="top-actions">
    <form method="get" action="/panel/comisiones" class="period-pick">
      <label class="sr-only" for="periodo">Corte</label>
      <select id="periodo" name="periodo" data-autosubmit>
        <?php foreach ($periods as $op): ?>
        <option value="<?= (int) $op['id'] ?>"<?= (int) $op['id'] === (int) $p['id'] ? ' selected' : '' ?>><?= e(ucfirst(ReportService::periodName($op))) ?> · <?= e(CommissionService::STATUS_LABELS[$op['status']]) ?></option>
        <?php endforeach; ?>
      </select>
      <noscript><button class="btn ghost" type="submit">Ver</button></noscript>
    </form>
    <span class="pill <?= e($pill) ?>"><?= e(CommissionService::STATUS_LABELS[$status] ?? $status) ?></span>
    <a class="btn ghost" href="/panel/comisiones/<?= (int) $p['id'] ?>/exportar.csv">Exportar CSV</a>
    <a class="btn ghost" href="/panel/comisiones/<?= (int) $p['id'] ?>/imprimir" target="_blank" rel="noopener">PDF</a>
  </div>
</div>

<div class="kpis">
  <div class="card kpi"><p class="lbl">Servicios realizados</p><p class="v sm"><?= e($fmt($x['servicesBase'])) ?></p><p class="small muted">COP · <?= $x['servicesCount'] ?> línea<?= $x['servicesCount'] === 1 ? '' : 's' ?> de reservas web</p></div>
  <div class="card kpi"><p class="lbl">Ventas en línea</p><p class="v sm"><?= e($fmt($x['ordersBase'])) ?></p><p class="small muted">COP · <?= $x['ordersCount'] ?> pago<?= $x['ordersCount'] === 1 ? '' : 's' ?> con Wompi</p></div>
  <div class="card kpi"><p class="lbl">Proyectado (confirmadas)</p><p class="v sm"><?= e($fmt($x['projectedBase'])) ?></p><p class="small muted">COP · <?= $x['projectedCount'] ?> aún no realizada<?= $x['projectedCount'] === 1 ? '' : 's' ?></p></div>
  <div class="card kpi dark"><p class="lbl">Comisión del corte</p><p class="v"><?= e($fmt($total)) ?></p><p class="small muted">COP · <?= e($fmt($x['servicesAmount'])) ?> servicios + <?= e($fmt($x['ordersAmount'])) ?> ventas</p></div>
</div>

<div class="split">
  <div class="stack">
    <section class="card" aria-labelledby="lq">
      <div class="card-head"><h2 id="lq" class="d">Servicios realizados</h2><span class="xs muted">Solo cuentan las reservas marcadas como realizadas</span></div>
      <?php if (!$x['services']): ?><p class="empty">Aún no hay servicios realizados en este corte.</p><?php else: ?>
      <div class="tbl-wrap"><table class="tbl">
        <thead><tr><th scope="col">Código</th><th scope="col">Servicio</th><th scope="col">Fecha</th><th scope="col">Origen</th><th scope="col" class="n">Valor</th><th scope="col" class="n">Tasa</th><th scope="col" class="n">Comisión</th></tr></thead>
        <tbody>
        <?php foreach ($x['services'] as $l): ?>
        <tr>
          <td class="nowrap"><a href="/panel/reservas/<?= e($l['code']) ?>"><?= e($l['code']) ?></a></td>
          <td><?= e(ReportService::serviceShort((string) $l['service_code'], (int) $l['people'], $l['event_type'])) ?><?php if ($l['kind'] === 'no_asistio'): ?> <span class="xs muted">· no asistió (abono)</span><?php endif; ?><?php if ($l['note']): ?><br><span class="xs muted"><?= e($l['note']) ?></span><?php endif; ?></td>
          <td class="nowrap"><?= e($day($l['starts_at'])) ?></td>
          <td><?= e(ReportService::originLabel((string) $l['source'])) ?></td>
          <td class="n"><?= e($fmt((int) $l['base'])) ?></td>
          <td class="n"><?= e(RateService::percent((int) $l['rate_bp'])) ?></td>
          <td class="n"><?= e($fmt((int) $l['amount'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="4">Total servicios</td><td class="n"><?= e($fmt($x['servicesBase'])) ?></td><td class="n"></td><td class="n"><?= e($fmt($x['servicesAmount'])) ?></td></tr></tfoot>
      </table></div>
      <?php endif; ?>
    </section>

    <section class="card" aria-labelledby="vl">
      <div class="card-head"><h2 id="vl" class="d">Ventas en línea</h2><span class="xs muted">Confirmadas por el webhook de Wompi</span></div>
      <?php if (!$x['orders']): ?><p class="empty">Sin ventas en línea en este corte.</p><?php else: ?>
      <div class="tbl-wrap"><table class="tbl">
        <thead><tr><th scope="col">Pedido</th><th scope="col">Producto</th><th scope="col">Pago</th><th scope="col">Referencia Wompi</th><th scope="col" class="n">Valor</th><th scope="col" class="n">Tasa</th><th scope="col" class="n">Comisión</th></tr></thead>
        <tbody>
        <?php foreach ($x['orders'] as $l): ?>
        <tr>
          <td class="nowrap"><?= e($l['reference']) ?></td>
          <td><?= e($l['products'] ?? '—') ?><?php if ($l['note']): ?><br><span class="xs muted"><?= e($l['note']) ?></span><?php endif; ?></td>
          <td class="nowrap"><?= e($day($l['paid_at'])) ?> · <?= $l['kind'] === 'reembolso' ? 'reembolso' : e($l['order_status']) ?></td>
          <td class="muted mono"><?= e($l['wompi_transaction_id'] ?? '—') ?></td>
          <td class="n"><?= e($fmt((int) $l['base'])) ?></td>
          <td class="n"><?= e(RateService::percent((int) $l['rate_bp'])) ?></td>
          <td class="n"><?= e($fmt((int) $l['amount'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="4">Total ventas</td><td class="n"><?= e($fmt($x['ordersBase'])) ?></td><td class="n"></td><td class="n"><?= e($fmt($x['ordersAmount'])) ?></td></tr></tfoot>
      </table></div>
      <?php endif; ?>
    </section>

    <section class="card" aria-labelledby="comentarios" id="comentarios">
      <div class="card-head"><h2 id="comentarios-t" class="d">Comentarios</h2><span class="xs muted">Visibles para ambas partes</span></div>
      <?php if (!$x['comments']): ?><p class="small muted">Todavía no hay comentarios sobre este corte.</p><?php endif; ?>
      <?php foreach ($x['comments'] as $c): $t = local_time($c['created_at']); ?>
      <div class="ok-line"><div><p class="small"><strong><?= e($c['name']) ?></strong> <span class="xs muted">· <?= $t ? e(fecha_corta($t) . ' ' . $t->format('G:i')) : '' ?></span></p><p><?= e($c['body']) ?></p></div></div>
      <?php endforeach; ?>
      <form method="post" action="/panel/comisiones/<?= (int) $p['id'] ?>/comentar" class="stack mt-12">
        <?= csrf_field() ?>
        <div class="f"><label for="cm">Nuevo comentario</label><textarea id="cm" name="body" maxlength="1000" required></textarea></div>
        <div><button class="btn ghost" type="submit">Comentar</button></div>
      </form>
    </section>
  </div>

  <div class="stack">
    <section class="card" aria-labelledby="ap">
      <h2 id="ap" class="d card-title">Cierre del corte</h2>
      <p class="small muted mt-8">El corte se cierra cuando ambas partes lo aprueban. Después ya no se puede editar.</p>
      <div class="mt-8">
        <?php foreach ($x['approvers'] as $r => $a): $t = local_time($a['at']); ?>
        <div class="ok-line">
          <span class="ic-c <?= $a['at'] ? 'yes' : 'no' ?>" aria-hidden="true"><?= $a['at'] ? '✓' : '·' ?></span>
          <div><p class="small"><?= e($a['name']) ?></p><p class="xs muted"><?= $a['at'] ? 'Aprobó el ' . e(fecha_corta($t) . ' · ' . $t->format('G:i')) : ($status === 'abierto' ? 'Aprueba a partir del día 1 del mes siguiente' : ($r === $role ? 'Pendiente de tu aprobación' : 'Pendiente')) ?></p></div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="btns mt-16">
        <form method="post" action="/panel/comisiones/<?= (int) $p['id'] ?>/aprobar" data-confirm="¿Apruebas el corte de <?= e(ReportService::periodName($p)) ?> por <?= e(money($total)) ?>?"><?= csrf_field() ?><button class="btn block" type="submit"<?= $canApprove ? '' : ' disabled' ?>>Aprobar corte</button></form>
        <a class="btn ghost" href="#comentarios">Comentar</a>
      </div>
      <?php if ($status === 'cerrado' && $role === 'owner'): ?>
      <form method="post" action="/panel/comisiones/<?= (int) $p['id'] ?>/pagado" class="stack mt-16" data-confirm="¿Marcar el corte como pagado?">
        <?= csrf_field() ?>
        <div class="f"><label for="pn">Fecha y medio de pago</label><input id="pn" name="note" maxlength="255" required placeholder="5 dic · transferencia Bancolombia"></div>
        <button class="btn" type="submit">Marcar pagado</button>
      </form>
      <?php endif; ?>
      <?php if ($status === 'pagado'): ?><p class="small mt-16"><span class="pill good">Pagado</span> <?= e($p['payment_note'] ?? '') ?></p><?php endif; ?>
      <?php if ($closed): ?>
      <div class="hashbox mt-16">
        <p class="xs muted">Huella de las líneas al cerrar (SHA-256)</p>
        <p class="mono"><?= e((string) $p['snapshot_hash']) ?></p>
        <p class="small"><?= $x['snapshotOk'] ? '<span class="pill good">Las líneas coinciden con la huella</span>' : '<span class="pill bad">Las líneas NO coinciden con la huella</span>' ?></p>
      </div>
      <?php endif; ?>
    </section>

    <section class="card" aria-labelledby="tx" id="tasas">
      <h2 id="tx" class="d card-title">Tasas pactadas</h2>
      <div class="mt-8">
        <?php foreach (RateService::CATEGORIES as $cat => $label): ?>
        <div class="row-line small"><span><?= e($label) ?></span><span class="num"><?= $rates[$cat] > 0 ? e(RateService::percent($rates[$cat])) : '<span class="muted">Sin tasa aprobada</span>' ?></span></div>
        <?php endforeach; ?>
      </div>
      <p class="xs muted mt-8">Cada reserva guarda la tasa vigente al crearse. Cambiar una tasa no altera reservas anteriores. Una tasa rige solo con la aprobación de ambas partes.</p>

      <?php foreach ($scheduledRates as $sr): ?>
      <p class="xs mt-8"><span class="pill good">Programada</span> <?= e(RateService::CATEGORIES[$sr['category']]) ?> <?= e(RateService::percent((int) $sr['rate_bp'])) ?> desde <?= e(fecha_corta(local_time($sr['valid_from']))) ?></p>
      <?php endforeach; ?>

      <?php if ($pendingRates): ?>
      <h3 class="lbl mt-16">Propuestas pendientes</h3>
      <?php foreach ($pendingRates as $pr): $mineCol = $role === 'owner' ? 'approved_by_owner_at' : 'approved_by_partner_at'; ?>
      <div class="ok-line">
        <div class="grow">
          <p class="small"><?= e(RateService::CATEGORIES[$pr['category']]) ?> · <strong><?= e(RateService::percent((int) $pr['rate_bp'])) ?></strong> desde <?= e(fecha_corta(local_time($pr['valid_from']))) ?></p>
          <p class="xs muted">Propuso <?= e($pr['proposer'] ?? '—') ?> · dueña <?= $pr['approved_by_owner_at'] ? '✓' : 'pendiente' ?> · socio <?= $pr['approved_by_partner_at'] ? '✓' : 'pendiente' ?></p>
        </div>
        <?php if ($pr[$mineCol] === null): ?>
        <form method="post" action="/panel/comisiones/tasas/<?= (int) $pr['id'] ?>/aprobar" data-confirm="¿Apruebas esta tasa?"><?= csrf_field() ?><button class="btn ghost" type="submit">Aprobar</button></form>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>

      <details class="action mt-16">
        <summary>Proponer una tasa</summary>
        <form method="post" action="/panel/comisiones/tasas" class="stack">
          <?= csrf_field() ?>
          <div class="f"><label for="rc">Categoría</label><select id="rc" name="category" required><?php foreach (RateService::CATEGORIES as $cat => $label): ?><option value="<?= e($cat) ?>"><?= e($label) ?></option><?php endforeach; ?></select></div>
          <div class="f"><label for="rp">Porcentaje</label><input id="rp" name="percent" inputmode="decimal" pattern="\d{1,2}([.,]\d{1,2})?" required placeholder="10"></div>
          <div class="f"><label for="rf">Vigente desde</label><input id="rf" type="date" name="valid_from" min="<?= e($today) ?>" value="<?= e($today) ?>" required></div>
          <button class="btn" type="submit">Enviar propuesta</button>
        </form>
      </details>
    </section>

    <section class="card" aria-labelledby="ca">
      <h2 id="ca" class="d card-title">Cortes anteriores</h2>
      <div class="mt-8">
        <?php $any = false; foreach ($periods as $op): if ((int) $op['id'] === (int) $p['id'] || $op['status'] === 'abierto') { continue; } $any = true; ?>
        <a class="row-line small rowlink" href="/panel/comisiones?periodo=<?= (int) $op['id'] ?>">
          <span><?= e(ucfirst(ReportService::periodName($op))) ?></span>
          <span class="num"><?= e($fmt((int) $op['total_commission'])) ?> · <?= $op['status'] === 'pagado' && $op['paid_at'] ? 'pagado ' . e(fecha_corta(local_time($op['paid_at']))) : e(mb_strtolower(CommissionService::STATUS_LABELS[$op['status']])) ?></span>
        </a>
        <?php endforeach; ?>
        <?php if (!$any): ?><p class="small muted">Todavía no hay otros cortes.</p><?php endif; ?>
      </div>
    </section>
  </div>
</div>
