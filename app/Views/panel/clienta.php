<?php
/** @var array $c @var list<array> $bookings @var list<array> $orders @var array $utm @var ?string $error */
use App\Controllers\Panel\ClientasController;
use App\Controllers\Panel\VentasController;
use App\Domain\Attribution\Source;
use App\Domain\Booking\BookingStateMachine;
use App\Panel\Auth;
$fmt = static fn(?string $utc): string => $utc ? fecha_corta(local_time($utc)) . ' · ' . hora(local_time($utc)) : '—';
$vigente = $c['web_attributed_until'] && $c['web_attributed_until'] >= now_utc();
$done = array_sum(array_map(static fn(array $b): int => $b['status'] === 'realizada' ? (int) $b['total'] : 0, $bookings));
?>
<div class="top">
  <div><p class="lbl"><a href="/panel/clientas">Clientas</a> / Ficha</p><h1 class="d"><?= e($c['name']) ?></h1></div>
  <div class="top-actions">
    <?php if ($c['anonymized_at']): ?><span class="pill">Anonimizada el <?= e(fecha_corta(local_time($c['anonymized_at']))) ?></span><?php endif; ?>
    <span class="pill <?= $vigente ? 'good' : '' ?>"><?= $vigente ? 'Atribuida a la web' : 'Sin atribución web vigente' ?></span>
  </div>
</div>
<?php if ($error): ?><div class="flash err" role="alert"><?= e($error) ?></div><?php endif; ?>

<div class="split">
  <div class="stack">
    <section class="card">
      <div class="card-head"><h2 class="d">Reservas</h2><p class="muted small"><?= count($bookings) ?> · realizado <?= e(money($done)) ?></p></div>
      <div class="tbl-wrap">
        <table class="tbl">
          <thead><tr><th scope="col">Código</th><th scope="col">Servicio</th><th scope="col">Fecha</th><th scope="col">Origen</th><th scope="col">Estado</th><th scope="col" class="n">Valor</th></tr></thead>
          <tbody>
          <?php if ($bookings === []): ?><tr><td colspan="6" class="empty">Sin reservas.</td></tr><?php endif; ?>
          <?php foreach ($bookings as $b): ?>
            <tr>
              <td class="nowrap"><a href="/panel/reservas/<?= e($b['code']) ?>"><?= e($b['code']) ?></a></td>
              <td><?= e($b['service_name']) ?></td>
              <td class="nowrap"><?= e($fmt($b['starts_at'])) ?></td>
              <td><?= e(Source::tryFrom($b['source'])?->label() ?? $b['source']) ?></td>
              <td><span class="st <?= e(BookingStateMachine::CHIP[$b['status']] ?? 'sol') ?>"><?= e(BookingStateMachine::LABELS[$b['status']] ?? $b['status']) ?></span></td>
              <td class="n"><?= e(money((int) $b['total'], false)) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php if ($orders !== []): ?>
    <section class="card">
      <div class="card-head"><h2 class="d">Compras en línea</h2></div>
      <div class="tbl-wrap">
        <table class="tbl">
          <thead><tr><th scope="col">Pedido</th><th scope="col">Fecha</th><th scope="col">Estado</th><th scope="col" class="n">Valor</th></tr></thead>
          <tbody>
          <?php foreach ($orders as $o): ?>
            <tr><td><?= e($o['reference']) ?></td><td><?= e($fmt($o['paid_at'] ?? $o['created_at'])) ?></td><td><span class="st <?= e(VentasController::CHIP[$o['status']] ?? 'sol') ?>"><?= e(VentasController::STATUS[$o['status']] ?? $o['status']) ?></span></td><td class="n"><?= e(money((int) $o['total'], false)) ?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php endif; ?>
  </div>

  <aside class="stack">
    <section class="card">
      <div class="facts facts-flush">
        <div><p class="lbl">WhatsApp</p><p><?= e(Auth::phone($c['phone_e164'])) ?></p></div>
        <div><p class="lbl">Correo</p><p><?= e(Auth::email($c['email'])) ?></p></div>
        <div><p class="lbl">Cumpleaños</p><p><?= $c['birthday'] ? e(Auth::is('partner') ? '••' : fecha_larga(new DateTimeImmutable((string) $c['birthday']), false)) : '—' ?></p></div>
        <div><p class="lbl">Primer origen</p><p><?= e(ClientasController::SOURCES[$c['first_source']] ?? $c['first_source']) ?></p></div>
        <div><p class="lbl">Primer contacto</p><p><?= e($fmt($c['first_seen_at'])) ?></p></div>
        <div><p class="lbl">Atribuida hasta</p><p><?= $c['web_attributed_until'] ? e(fecha_corta(local_time($c['web_attributed_until']))) : '—' ?></p></div>
      </div>
    </section>
    <section class="card">
      <h2 class="d card-title">Consentimientos</h2>
      <div class="row-line"><span>Tratamiento de datos (Ley 1581)</span><span class="muted small nowrap"><?= e($fmt($c['consent_data_at'])) ?></span></div>
      <div class="row-line"><span>Novedades por correo</span><span class="muted small nowrap"><?= $c['consent_marketing_at'] ? e($fmt($c['consent_marketing_at'])) : 'No autorizado' ?></span></div>
    </section>
    <section class="card">
      <h2 class="d card-title">Primer registro web</h2>
      <?php if ($utm === []): ?>
        <p class="muted small">Sin UTM ni referente guardados.</p>
      <?php else: ?>
        <?php foreach ($utm as $k => $v): ?>
        <div class="row-line"><span class="small"><?= e($k) ?></span><span class="small mono"><?= e(is_scalar($v) ? (string) $v : json_encode($v)) ?></span></div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
    <?php if (Auth::is('owner') && !$c['anonymized_at']): ?>
    <section class="card">
      <details class="action">
        <summary>Anonimizar datos (supresión)</summary>
        <form method="post" action="/panel/clientas/<?= (int) $c['id'] ?>/anonimizar" class="stack" data-confirm="¿Anonimizar los datos de esta clienta? No se puede deshacer.">
          <?= csrf_field() ?>
          <p class="small muted">Borra nombre, teléfono, correo, cumpleaños, dirección y UTM. Las reservas, pedidos y comisiones se conservan para efectos contables. No se puede deshacer.</p>
          <div class="f"><label for="an-reason">Motivo</label><input id="an-reason" name="reason" maxlength="300" required placeholder="Solicitud de supresión recibida por correo el …"></div>
          <label class="ckb"><input type="checkbox" name="confirm" value="1" required> Confirmo que la clienta pidió la supresión</label>
          <button class="btn danger" type="submit">Anonimizar</button>
        </form>
      </details>
    </section>
    <?php endif; ?>
  </aside>
</div>
