<?php
/**
 * Resumen (Panel-Resumen.dc.html).
 * @var array $d @var int $year @var int $month @var string $prev @var ?string $next @var list<array> $alerts
 */
use App\Domain\Booking\BookingStateMachine;
use App\Domain\Commission\ReportService;

$monthName = ucfirst(mes_nombre($month));
$lastDay = (int) $d['range']['end']->modify('-1 day')->format('j');
$abbr = mb_substr(mes_nombre($month), 0, 3);
$delta = $d['requestsDelta'];
?>
<div class="top">
  <div class="stack gap-8"><p class="lbl">Resumen del mes</p><h1 class="d"><?= e($monthName . ' ' . $year) ?></h1></div>
  <div class="top-actions">
    <nav class="month-nav" aria-label="Cambiar de mes">
      <a class="btn ghost icon" href="/panel?mes=<?= e($prev) ?>" aria-label="Mes anterior">‹</a>
      <span class="pill">Corte: 1 – <?= $lastDay ?> <?= e($abbr) ?></span>
      <?php if ($next): ?><a class="btn ghost icon" href="/panel?mes=<?= e($next) ?>" aria-label="Mes siguiente">›</a><?php endif; ?>
    </nav>
    <a class="btn ghost" href="/panel/comisiones">Ver comisiones</a>
  </div>
</div>

<div class="kpis">
  <div class="card kpi"><p class="lbl">Solicitudes desde la web</p><p class="v"><?= e((string) $d['requests']) ?></p>
    <p class="small <?= $delta >= 0 ? 'up' : 'muted' ?>"><?= $delta >= 0 ? '+' : '−' ?><?= e((string) abs($delta)) ?> frente a <?= e($d['prevMonthName']) ?></p></div>
  <div class="card kpi"><p class="lbl">Confirmadas con abono</p><p class="v"><?= e((string) $d['confirmed']) ?></p><p class="small muted"><?= e((string) $d['confirmedPct']) ?> % de las solicitudes</p></div>
  <div class="card kpi"><p class="lbl">Realizadas</p><p class="v"><?= e((string) $d['done']) ?></p><p class="small muted"><?= e((string) $d['pending']) ?> pendientes este mes</p></div>
  <div class="card kpi dark"><p class="lbl">Comisión estimada</p><p class="v"><?= e(money($d['commissionProjected'], false)) ?></p><p class="small muted">COP · <?= e(money($d['commission'], false)) ?> ya generada + confirmadas</p></div>
</div>

<div class="g2">
  <section class="card" aria-labelledby="emb">
    <div class="card-head"><h2 id="emb" class="d">Embudo de la web</h2><span class="xs muted">Contadores propios + panel</span></div>
    <?php foreach ($d['funnel'] as $i => $f): ?>
    <div class="between"><span><?= e($f['label']) ?></span><span class="num"><?= e(number_format($f['n'], 0, ',', '.')) ?></span></div>
    <div class="bar b<?= $i + 1 ?>"><span class="w-<?= (int) $f['w'] ?>"></span></div>
    <?php endforeach; ?>
  </section>

  <section class="card" aria-labelledby="ori">
    <div class="card-head"><h2 id="ori" class="d">Origen de las reservas</h2><span class="xs muted">Atribuidas a la web</span></div>
    <?php foreach ($d['origin'] as $o): ?>
    <div class="row-line"><span><?= e($o['label']) ?></span><span class="num"><?= e((string) $o['n']) ?></span></div>
    <?php endforeach; ?>
    <p class="xs muted mt-12">Una clienta queda atribuida a la web durante <?= (int) config('app.attribution.window_months', 6) ?> meses desde su primer registro, identificada por su número de WhatsApp.</p>
  </section>

  <section class="card" aria-labelledby="rev">
    <div class="card-head"><h2 id="rev" class="d">Requiere revisión</h2><?php if ($alerts): ?><span class="pill bad"><?= count($alerts) ?> alerta<?= count($alerts) === 1 ? '' : 's' ?></span><?php endif; ?></div>
    <?php if (!$alerts): ?><p class="empty">Sin alertas en los últimos 45 días.</p><?php endif; ?>
    <?php foreach ($alerts as $a): $t = local_time($a['created_at']); ?>
    <div class="alert">
      <span class="dot<?= in_array($a['type'], ['encuesta_contradictoria', 'cancelacion_web'], true) ? ' bad' : '' ?>" aria-hidden="true"></span>
      <div>
        <p><?= e($a['message']) ?></p>
        <p class="xs muted"><?= $t ? e(fecha_corta($t)) : '' ?><?= !empty($a['code']) ? ' · ' : '' ?><?php if (!empty($a['code'])): ?><a href="/panel/reservas/<?= e($a['code']) ?>"><?= e($a['code']) ?></a><?php endif; ?></p>
      </div>
    </div>
    <?php endforeach; ?>
  </section>

  <section class="card" aria-labelledby="prox">
    <div class="card-head"><h2 id="prox" class="d">Próximas citas</h2><a class="xs" href="/panel/reservas">Ver agenda</a></div>
    <?php if (!$d['upcoming']): ?><p class="empty">No hay citas próximas.</p><?php else: ?>
    <div class="tbl-wrap"><table class="tbl">
      <thead><tr><th scope="col">Fecha</th><th scope="col">Clienta</th><th scope="col">Servicio</th><th scope="col">Estado</th></tr></thead>
      <tbody>
      <?php foreach ($d['upcoming'] as $u): $t = local_time($u['starts_at']); ?>
      <tr>
        <td class="nowrap"><a href="/panel/reservas/<?= e($u['code']) ?>"><?= $t ? e((int) $t->format('j') . ' ' . mb_substr(mes_nombre((int) $t->format('n')), 0, 3) . ' · ' . $t->format('G:i')) : '' ?></a></td>
        <td><?= e(ReportService::shortName((string) $u['client_name'])) ?></td>
        <td><?= e(ReportService::serviceShort((string) $u['service_code'], (int) $u['people'], $u['event_type'])) ?></td>
        <td><span class="st <?= e(BookingStateMachine::CHIP[$u['status']] ?? 'sol') ?>"><?= e(BookingStateMachine::LABELS[$u['status']] ?? $u['status']) ?></span></td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php endif; ?>
  </section>
</div>
