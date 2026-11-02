<?php
/**
 * Calendario mensual del panel.
 * @var array{start:DateTimeImmutable,days:array<string,array{bookings:list<array>,blocks:list<array>,holiday:?string}>} $cal
 * @var string $today @var string $prev @var string $next
 */
use App\Domain\Booking\BookingStateMachine;

$start = $cal['start'];
$svcShort = ['pack1' => 'P1', 'pack2' => 'P2', 'pack3' => 'P3'];
$total = 0;
foreach ($cal['days'] as $d) {
    $total += count(array_filter($d['bookings'], static fn($b) => $b['status'] !== 'realizada'));
}
?>
<div class="top">
  <div class="stack gap-8"><p class="lbl">Agenda</p><h1 class="d"><?= e(ucfirst(mes_nombre((int) $start->format('n'))) . ' ' . $start->format('Y')) ?></h1></div>
  <div class="top-actions">
    <span class="pill"><?= e((string) $total) ?> citas activas</span>
    <a class="btn ghost" href="/panel/calendario?mes=<?= e($prev) ?>" aria-label="Mes anterior">←</a>
    <a class="btn ghost" href="/panel/calendario">Hoy</a>
    <a class="btn ghost" href="/panel/calendario?mes=<?= e($next) ?>" aria-label="Mes siguiente">→</a>
  </div>
</div>
<section class="card" aria-label="Calendario de <?= e(mes_nombre((int) $start->format('n'))) ?>">
  <div class="pcal" role="list" aria-label="Días del mes">
    <?php foreach (['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'] as $h): ?><div class="h" aria-hidden="true"><?= $h ?></div><?php endforeach; ?>
    <?php for ($i = 1; $i < (int) $start->format('N'); $i++): ?><div class="cell out" aria-hidden="true"></div><?php endfor; ?>
    <?php foreach ($cal['days'] as $ymd => $d):
        $cls = 'cell' . ($ymd === $today ? ' today' : '') . (($d['holiday'] || $d['blocks']) ? ' closed' : ''); ?>
    <div class="<?= $cls ?>" role="listitem">
      <span class="dn"><span aria-hidden="true"><?= (int) substr($ymd, 8, 2) ?></span><span class="sr-only"><?= e(fecha_larga(new DateTimeImmutable($ymd))) ?><?= $ymd === $today ? ' (hoy)' : '' ?><?= $d['bookings'] === [] && !$d['holiday'] && !$d['blocks'] ? ': sin citas' : '' ?></span></span>
      <?php if ($d['holiday']): ?><span class="blk" title="Festivo"><?= e($d['holiday']) ?></span><?php endif; ?>
      <?php foreach ($d['blocks'] as $bl): ?><span class="blk">Bloqueo<?= $bl['reason'] ? ' · ' . e($bl['reason']) : '' ?></span><?php endforeach; ?>
      <?php foreach ($d['bookings'] as $b): $t = local_time($b['starts_at']); ?>
      <a class="bk<?= $b['status'] === 'solicitada' ? ' sol' : '' ?><?= $b['status'] === 'realizada' ? ' done' : '' ?>" href="/panel/reservas/<?= e($b['code']) ?>" title="<?= e($b['code'] . ' · ' . BookingStateMachine::LABELS[$b['status']]) ?>"><?= e($t ? $t->format('G:i') : '') ?> <span><?= e(explode(' ', (string) $b['client_name'])[0]) ?> · <?= e($svcShort[$b['service_code']] ?? '') ?></span></a>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
  <p class="xs muted cal-legend"><span class="lg con"></span> Confirmada <span class="lg sol"></span> Solicitada (cupo retenido) <span class="lg done"></span> Realizada <span class="lg blk"></span> Festivo o bloqueo</p>
</section>
