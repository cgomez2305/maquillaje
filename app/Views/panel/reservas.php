<?php
/**
 * Panel · Reservas (Panel-Reservas.dc.html): listado con filtros + detalle lateral.
 * @var array{rows:list<array>,total:int,page:int,pages:int} $list
 * @var string $filter @var string $q @var string $sort @var string $role @var string $back
 * @var ?array $cur @var list<array>|null $events @var list<array>|null $files @var list<string>|null $transitions
 * @var bool|null $canEdit @var bool|null $canEditValue @var array|null $packs @var list<string>|null $slots
 */
use App\Domain\Attribution\Source;
use App\Domain\Booking\BookingAdminService;
use App\Domain\Booking\BookingStateMachine;
use App\Domain\Commission\RateService;
use App\Panel\Auth;

$svcShort = ['pack1' => 'Pack 1', 'pack2' => 'Pack 2', 'pack3' => 'Pack 3'];
$evNames = config('app.booking.event_types');
$svcLabel = static function (array $b) use ($svcShort, $evNames): string {
    $s = $svcShort[$b['service_code']] ?? $b['service_name'];
    if ($b['service_code'] === 'pack3' && in_array($b['event_type'], ['boda', 'quince'], true)) {
        $s .= ' · ' . ($b['event_type'] === 'boda' ? 'Novia' : 'Quince');
    }
    return $s . ((int) $b['people'] > 1 ? ' ×' . (int) $b['people'] : '');
};
$keep = static fn(array $extra = []): string => ($qs = http_build_query(array_filter(['estado' => $filter, 'q' => $q, 'orden' => $sort !== 'fecha' ? $sort : ''] + $extra, static fn($v) => $v !== '' && $v !== null))) ? '?' . $qs : '';
$hidden = static function () use ($filter, $q, $sort): string {
    $h = '';
    foreach (['estado' => $filter, 'q' => $q, 'orden' => $sort !== 'fecha' ? $sort : ''] as $k => $v) {
        if ($v !== '') {
            $h .= '<input type="hidden" name="' . e($k) . '" value="' . e($v) . '">';
        }
    }
    return $h;
};
$canWrite = in_array($role, ['owner', 'staff'], true);
?>
<div class="top">
  <div class="stack gap-8"><p class="lbl">Agenda y registro</p><h1 class="d">Reservas</h1></div>
  <div class="top-actions">
    <form class="search" role="search" method="get" action="/panel/reservas">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
      <label class="sr-only" for="buscar">Buscar por código, nombre o teléfono</label>
      <input id="buscar" type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por código, nombre o teléfono" autocomplete="off" data-search>
      <?php if ($filter !== ''): ?><input type="hidden" name="estado" value="<?= e($filter) ?>"><?php endif; ?>
    </form>
    <?php if ($canWrite): ?><a class="btn" href="/panel/reservas/nueva">Nueva reserva manual</a><?php endif; ?>
  </div>
</div>

<nav class="chips" aria-label="Filtrar por estado">
  <?php foreach (BookingAdminService::FILTERS as $key => [$label]): ?>
  <a class="chip" href="/panel/reservas<?= e((($qs = http_build_query(array_filter(['estado' => $key, 'q' => $q], static fn($v) => $v !== ''))) ? '?' . $qs : '')) ?>"<?= $filter === $key ? ' aria-current="true"' : '' ?>><?= e($label) ?></a>
  <?php endforeach; ?>
</nav>

<div class="split<?= $cur ? '' : ' no-drawer' ?>">
  <section class="card rv-list" aria-label="Listado de reservas">
    <?php if ($list['rows'] === []): ?>
    <p class="empty"><?= $q !== '' || $filter !== '' ? 'No hay reservas con ese filtro.' : 'Todavía no hay reservas registradas.' ?></p>
    <?php else: ?>
    <div class="tbl-wrap" tabindex="0" role="region" aria-label="Tabla desplazable">
      <table class="tbl rv-tbl">
        <thead><tr>
          <th scope="col">Código</th><th scope="col">Clienta</th><th scope="col">Servicio</th>
          <th scope="col"><a class="sort" href="/panel/reservas<?= e($keep(['orden' => $sort === 'fecha_asc' ? '' : 'fecha_asc'])) ?>">Fecha<span aria-hidden="true"><?= $sort === 'fecha_asc' ? ' ↑' : ' ↓' ?></span><span class="sr-only">, cambiar orden</span></a></th>
          <th scope="col">Origen</th><th scope="col">Estado</th><th scope="col" class="n">Valor</th>
        </tr></thead>
        <tbody>
          <?php foreach ($list['rows'] as $r): $href = '/panel/reservas/' . $r['code'] . $keep(['p' => $list['page'] > 1 ? (string) $list['page'] : '']); $sel = $cur && $cur['code'] === $r['code']; ?>
          <tr<?= $sel ? ' class="sel" aria-current="true"' : '' ?>>
            <td class="code"><a class="rowlink" href="<?= e($href) ?>"><span class="sr-only">Ver reserva </span><?= e($r['code']) ?></a></td>
            <td><a class="rowlink" href="<?= e($href) ?>" tabindex="-1"><?= e($r['client_name']) ?></a></td>
            <td><a class="rowlink" href="<?= e($href) ?>" tabindex="-1"><?= e($svcLabel($r)) ?></a></td>
            <td class="nowrap"><a class="rowlink" href="<?= e($href) ?>" tabindex="-1"><?= e(BookingAdminService::shortDate($r['starts_at'])) ?></a></td>
            <td class="origin"><a class="rowlink" href="<?= e($href) ?>" tabindex="-1"><?= e(Source::from($r['source'])->label()) ?></a></td>
            <td><a class="rowlink" href="<?= e($href) ?>" tabindex="-1"><span class="st <?= e(BookingStateMachine::CHIP[$r['status']] ?? '') ?>"><?= e(BookingStateMachine::LABELS[$r['status']] ?? $r['status']) ?></span></a></td>
            <td class="n"><a class="rowlink" href="<?= e($href) ?>" tabindex="-1"><?= e(money((int) $r['total'])) ?></a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php if ($list['pages'] > 1): ?>
    <nav class="pager" aria-label="Páginas">
      <?php if ($list['page'] > 1): ?><a class="btn ghost" href="/panel/reservas<?= e($keep(['p' => (string) ($list['page'] - 1)])) ?>">Anterior</a><?php endif; ?>
      <span class="small muted">Página <?= e((string) $list['page']) ?> de <?= e((string) $list['pages']) ?> · <?= e((string) $list['total']) ?> reservas</span>
      <?php if ($list['page'] < $list['pages']): ?><a class="btn ghost" href="/panel/reservas<?= e($keep(['p' => (string) ($list['page'] + 1)])) ?>">Siguiente</a><?php endif; ?>
    </nav>
    <?php else: ?>
    <p class="small muted list-count"><?= e((string) $list['total']) ?> <?= $list['total'] === 1 ? 'reserva' : 'reservas' ?></p>
    <?php endif; ?>
    <?php endif; ?>
  </section>

  <?php if ($cur): $c = $cur;
    $chip = BookingStateMachine::CHIP[$c['status']] ?? '';
    $start = local_time($c['starts_at']);
    $codePath = '/panel/reservas/' . $c['code'];
  ?>
  <aside class="card drawer" aria-label="Detalle de la reserva <?= e($c['code']) ?>">
    <div class="dr-head">
      <p class="lbl"><?= e($c['code']) ?></p>
      <span class="st <?= e($chip) ?>"><?= e(BookingStateMachine::LABELS[$c['status']] ?? $c['status']) ?></span>
    </div>
    <div>
      <h2 class="d"><?= e($c['client_name']) ?></h2>
      <p class="dr-sub"><?= e($svcLabel($c)) ?> · <?= e(BookingAdminService::shortDate($c['starts_at'])) ?></p>
    </div>
    <div class="facts">
      <div><p class="lbl">WhatsApp</p><p><?= e(Auth::phone($c['phone_e164'])) ?></p></div>
      <div><p class="lbl">Origen</p><p><?= e(Source::from($c['source'])->label()) ?></p></div>
      <div><p class="lbl">Valor total</p><p><?= e(money((int) $c['total'])) ?></p></div>
      <div><p class="lbl">Abono</p><p><?= e(money((int) $c['deposit_amount'])) ?> · <?= $c['deposit_confirmed_at'] ? ($files ? 'comprobante adjunto' : 'confirmado') : 'pendiente' ?></p></div>
      <div><p class="lbl">Correo</p><p class="wrap-any"><?= e(Auth::email($c['client_email'])) ?></p></div>
      <div><p class="lbl">Lugar</p><p><?= $c['location'] === 'home' ? 'A domicilio' : 'Estudio' ?></p></div>
      <?php if ($c['location'] === 'home' && $c['address']): ?><div class="full"><p class="lbl">Dirección</p><p><?= e($role === 'partner' ? '••• (dato de la clienta)' : $c['address']) ?></p></div><?php endif; ?>
      <div><p class="lbl">Evento</p><p><?= e($evNames[$c['event_type']] ?? ($c['event_type'] ?: '—')) ?></p></div>
      <div><p class="lbl">Atribución</p><p><?= (int) $c['is_web_attributed'] ? 'Web · tasa ' . e(RateService::percent((int) $c['commission_rate_bp'])) : 'Fuera de la web' ?></p></div>
      <?php if ($c['status'] === 'solicitada' && $c['hold_expires_at']): ?><div class="full"><p class="lbl">Cupo retenido hasta</p><p><?= e(BookingAdminService::shortDate($c['hold_expires_at'])) ?></p></div><?php endif; ?>
      <?php if ($c['notes']): ?><div class="full"><p class="lbl">Nota</p><p><?= e($c['notes']) ?></p></div><?php endif; ?>
    </div>

    <div>
      <p class="lbl">Historial (no se puede borrar)</p>
      <ol class="tl dr-tl">
        <?php foreach ($events as $ev): $sys = $ev['actor_user_id'] === null; ?>
        <li class="ev<?= $sys ? ' sys' : '' ?>">
          <p><?= e(BookingAdminService::eventLabel($ev)) ?></p>
          <?php if ($ev['reason']): ?><p class="why">Motivo: «<?= e($ev['reason']) ?>»</p><?php endif; ?>
          <p class="meta"><?= e(BookingAdminService::shortDate($ev['created_at'])) ?> · <?= e($sys ? ($ev['type'] === 'creada' ? 'la clienta' : 'sistema') : ($ev['actor_name'] ?? 'usuario')) ?></p>
        </li>
        <?php endforeach; ?>
      </ol>
    </div>

    <div>
      <p class="lbl">Comprobantes</p>
      <?php if ($files): ?>
      <ul class="files">
        <?php foreach ($files as $f): ?>
        <li><a href="/panel/archivos/<?= e((string) $f['id']) ?>"><?= e($f['mime'] === 'application/pdf' ? 'PDF' : 'Imagen') ?> · <?= e(BookingAdminService::shortDate($f['created_at'])) ?></a> <span class="xs muted"><?= e(number_format((int) $f['size_bytes'] / 1024, 0, ',', '.')) ?> KB · <?= e($f['uploader'] ?? '') ?></span></li>
        <?php endforeach; ?>
      </ul>
      <?php else: ?><p class="small muted mt-6">Sin comprobantes adjuntos.</p><?php endif; ?>
      <?php if ($canWrite): ?>
      <form class="upload" method="post" action="<?= e($codePath) ?>/comprobante" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <label class="sr-only" for="f-file">Archivo del comprobante</label>
        <input id="f-file" type="file" name="file" accept="image/jpeg,image/png,image/webp,application/pdf" required>
        <button class="btn ghost" type="submit">Adjuntar</button>
        <p class="xs muted full">JPG, PNG, WEBP o PDF de hasta 5 MB.</p>
      </form>
      <?php endif; ?>
    </div>

    <?php if ($canWrite && ($transitions || $canEdit)): ?>
    <div class="dr-actions">
      <?php if (in_array('confirmada', $transitions, true) && $c['status'] === 'solicitada'): ?>
      <form method="post" action="<?= e($codePath) ?>/estado" data-confirm="¿Confirmar el abono de <?= e(money((int) $c['deposit_amount'])) ?>? Se avisará a la clienta por correo.">
        <?= csrf_field() ?><?= $hidden() ?><input type="hidden" name="to" value="confirmada">
        <button class="btn block" type="submit">Confirmar abono</button>
      </form>
      <?php endif; ?>
      <?php if (in_array('confirmada', $transitions, true) && $c['status'] === 'reprogramada'): ?>
      <form method="post" action="<?= e($codePath) ?>/estado"><?= csrf_field() ?><?= $hidden() ?><input type="hidden" name="to" value="confirmada"><button class="btn block" type="submit">Confirmar</button></form>
      <?php endif; ?>
      <?php if (in_array('realizada', $transitions, true)): ?>
      <form method="post" action="<?= e($codePath) ?>/estado" data-confirm="¿Marcar la reserva como realizada?">
        <?= csrf_field() ?><?= $hidden() ?><input type="hidden" name="to" value="realizada">
        <button class="btn ghost block" type="submit">Marcar realizada</button>
      </form>
      <?php endif; ?>

      <?php if (in_array('reprogramada', $transitions, true)): ?>
      <details class="action full">
        <summary>Reprogramar</summary>
        <form class="fg" method="post" action="<?= e($codePath) ?>/estado">
          <?= csrf_field() ?><?= $hidden() ?><input type="hidden" name="to" value="reprogramada">
          <div class="f"><label for="rp-date">Nueva fecha</label><input id="rp-date" type="date" name="date" required min="<?= e((new DateTimeImmutable('now', App\Domain\Booking\Availability::tz()))->format('Y-m-d')) ?>"></div>
          <div class="f"><label for="rp-time">Hora</label><select id="rp-time" name="time" required><?php foreach ($slots as $t): ?><option value="<?= e($t) ?>"><?= e(hora(new DateTimeImmutable('2000-01-01 ' . $t))) ?></option><?php endforeach; ?></select></div>
          <div class="f full"><label for="rp-why">Motivo</label><input id="rp-why" name="reason" maxlength="500" placeholder="Opcional"></div>
          <div class="full"><button class="btn ghost block" type="submit">Guardar nueva fecha</button></div>
        </form>
      </details>
      <?php endif; ?>

      <?php if (in_array('no_asistio', $transitions, true)): ?>
      <details class="action full">
        <summary>No asistió</summary>
        <form class="stack gap-12" method="post" action="<?= e($codePath) ?>/estado">
          <?= csrf_field() ?><?= $hidden() ?><input type="hidden" name="to" value="no_asistio">
          <div class="f"><label for="na-why">Motivo (obligatorio)</label><textarea id="na-why" name="reason" required maxlength="500" rows="2"></textarea></div>
          <button class="btn danger block" type="submit">Marcar «No asistió»</button>
        </form>
      </details>
      <?php endif; ?>

      <?php if (in_array('cancelada', $transitions, true)): ?>
      <details class="action full danger-zone">
        <summary>Cancelar con motivo</summary>
        <form class="stack gap-12" method="post" action="<?= e($codePath) ?>/estado" data-confirm="¿Cancelar la reserva <?= e($c['code']) ?>? No se puede deshacer.">
          <?= csrf_field() ?><?= $hidden() ?><input type="hidden" name="to" value="cancelada">
          <div class="f"><label for="cn-why">Motivo (obligatorio)</label><textarea id="cn-why" name="reason" required maxlength="500" rows="2"></textarea></div>
          <button class="btn danger block" type="submit">Cancelar reserva</button>
        </form>
      </details>
      <?php endif; ?>

      <?php if ($canEdit): ?>
      <details class="action full">
        <summary>Editar datos</summary>
        <form class="fg" method="post" action="<?= e($codePath) ?>/editar">
          <?= csrf_field() ?><?= $hidden() ?>
          <?php if ($canEditValue): ?>
          <div class="f"><label for="ed-svc">Servicio</label><select id="ed-svc" name="service"><?php foreach ($packs as $code => $p): ?><option value="<?= e($code) ?>"<?= $c['service_code'] === $code ? ' selected' : '' ?>><?= e($svcShort[$code]) ?> · <?= e(money((int) $p['price'])) ?></option><?php endforeach; ?></select></div>
          <div class="f"><label for="ed-ppl">Personas</label><select id="ed-ppl" name="people"><?php for ($i = 1; $i <= 4; $i++): ?><option value="<?= $i ?>"<?= (int) $c['people'] === $i ? ' selected' : '' ?>><?= $i === 4 ? '4 o más' : $i ?></option><?php endfor; ?></select></div>
          <div class="f full"><label for="ed-total">Valor total (COP)</label><input id="ed-total" name="total" inputmode="numeric" value="<?= e((string) $c['total']) ?>"></div>
          <?php else: ?>
          <input type="hidden" name="service" value="<?= e($c['service_code']) ?>"><input type="hidden" name="people" value="<?= e((string) $c['people']) ?>"><input type="hidden" name="total" value="<?= e((string) $c['total']) ?>">
          <?php endif; ?>
          <?php if ($c['location'] === 'home'): ?>
          <div class="f full"><label for="ed-addr">Dirección</label><input id="ed-addr" name="address" maxlength="255" value="<?= e($c['address'] ?? '') ?>"></div>
          <?php endif; ?>
          <div class="f full"><label for="ed-notes">Nota</label><textarea id="ed-notes" name="notes" maxlength="1000" rows="2"><?= e($c['notes'] ?? '') ?></textarea></div>
          <?php if ($c['status'] !== 'solicitada' && $canEditValue): ?>
          <div class="f full"><label for="ed-why">Motivo del cambio</label><input id="ed-why" name="reason" maxlength="500"><span class="hint">Obligatorio si cambias el valor o el servicio: se avisa al socio.</span></div>
          <?php endif; ?>
          <div class="full"><button class="btn ghost block" type="submit">Guardar cambios</button></div>
        </form>
      </details>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <p class="xs muted dr-note">Cada cambio queda registrado con usuario, fecha y motivo. Las reservas nunca se eliminan.</p>
    <a class="small dr-close" href="/panel/reservas<?= e($keep()) ?>">Cerrar detalle</a>
  </aside>
  <?php endif; ?>
</div>
