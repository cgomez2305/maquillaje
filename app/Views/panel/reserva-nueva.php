<?php
/**
 * Alta manual de una reserva (WhatsApp, llamada, Instagram…). La atribución la calcula el sistema.
 * @var array<string,string> $errors @var array<string,mixed> $old @var array $packs @var list<string> $slots
 */
use App\Domain\Booking\BookingAdminService;

$err = static fn(string $f): string => isset($errors[$f]) ? '<span class="err" id="e-' . e($f) . '">' . e($errors[$f]) . '</span>' : '';
$aria = static fn(string $f): string => isset($errors[$f]) ? ' aria-invalid="true" aria-describedby="e-' . e($f) . '"' : '';
$v = static fn(string $f): string => e((string) ($old[$f] ?? ''));
$svcShort = ['pack1' => 'Pack 1 · Maquillaje social', 'pack2' => 'Pack 2 · Full experience', 'pack3' => 'Pack 3 · Novias y quinceañeras'];
?>
<link rel="stylesheet" href="<?= e(asset('css/panel-reservas.css')) ?>">
<div class="top">
  <div class="stack gap-8"><p class="lbl"><a href="/panel/reservas">Reservas</a> / Nueva</p><h1 class="d">Reserva manual</h1></div>
</div>

<?php if (isset($errors['form'])): ?><div class="flash err" role="alert"><?= e($errors['form']) ?></div><?php elseif ($errors): ?><div class="flash err" role="alert">Revisa los campos marcados.</div><?php endif; ?>

<div class="split">
  <form class="card stack" method="post" action="/panel/reservas/nueva" novalidate>
    <?= csrf_field() ?>
    <fieldset class="nb-set">
      <legend class="lbl">Clienta</legend>
      <div class="fg">
        <div class="f"><label for="n-phone">WhatsApp</label><input id="n-phone" name="phone" type="tel" inputmode="tel" required maxlength="30" value="<?= $v('phone') ?>" placeholder="300 123 4567"<?= $aria('phone') ?>><?= $err('phone') ?><span class="hint">Se busca por teléfono: si ya existe, se reutiliza.</span></div>
        <div class="f"><label for="n-name">Nombre completo</label><input id="n-name" name="name" required maxlength="120" value="<?= $v('name') ?>"<?= $aria('name') ?>><?= $err('name') ?></div>
        <div class="f"><label for="n-email">Correo (opcional)</label><input id="n-email" name="email" type="email" maxlength="190" value="<?= $v('email') ?>"<?= $aria('email') ?>><?= $err('email') ?><span class="hint">Con correo, la clienta recibe el código y los datos de pago.</span></div>
        <div class="f"><label for="n-bday">Cumpleaños (opcional)</label><input id="n-bday" name="birthday" type="date" value="<?= $v('birthday') ?>"<?= $aria('birthday') ?>><?= $err('birthday') ?></div>
        <div class="f"><label for="n-src">¿Cómo llegó?</label><select id="n-src" name="first_source" required<?= $aria('first_source') ?>><option value="">Elige</option><?php foreach (BookingAdminService::FIRST_SOURCES as $k => $label): ?><option value="<?= e($k) ?>"<?= ($old['first_source'] ?? '') === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select><?= $err('first_source') ?></div>
        <div class="f"><label for="n-lead">Código WEB del mensaje (si lo trae)</label><input id="n-lead" name="lead_code" maxlength="20" placeholder="WEB-2611-0087" value="<?= $v('lead_code') ?>"<?= $aria('lead_code') ?>><?= $err('lead_code') ?></div>
      </div>
    </fieldset>
    <fieldset class="nb-set">
      <legend class="lbl">Servicio y fecha</legend>
      <div class="fg">
        <div class="f"><label for="n-svc">Servicio</label><select id="n-svc" name="service"<?= $aria('service') ?>><?php foreach ($packs as $code => $p): ?><option value="<?= e($code) ?>"<?= ($old['service'] ?? '') === $code ? ' selected' : '' ?>><?= e($svcShort[$code] ?? $code) ?> · <?= e(money((int) $p['price'])) ?></option><?php endforeach; ?></select><?= $err('service') ?></div>
        <div class="f"><label for="n-ppl">Personas</label><select id="n-ppl" name="people"<?= $aria('people') ?>><?php for ($i = 1; $i <= 4; $i++): ?><option value="<?= $i ?>"<?= (int) ($old['people'] ?? 1) === $i ? ' selected' : '' ?>><?= $i === 4 ? '4 o más' : $i ?></option><?php endfor; ?></select><?= $err('people') ?></div>
        <div class="f"><label for="n-date">Fecha</label><input id="n-date" name="date" type="date" required value="<?= $v('date') ?>"<?= $aria('date') ?>><?= $err('date') ?></div>
        <div class="f"><label for="n-time">Hora de inicio</label><input id="n-time" name="time" type="time" required step="900" list="n-slots" value="<?= $v('time') ?>"><datalist id="n-slots"><?php foreach ($slots as $t): ?><option value="<?= e($t) ?>"></option><?php endforeach; ?></datalist></div>
        <div class="f"><label for="n-loc">Lugar</label><select id="n-loc" name="location"<?= $aria('location') ?>><option value="studio"<?= ($old['location'] ?? '') === 'studio' ? ' selected' : '' ?>>Estudio, Floridablanca</option><option value="home"<?= ($old['location'] ?? '') === 'home' ? ' selected' : '' ?>>A domicilio</option></select><?= $err('location') ?></div>
        <div class="f"><label for="n-ev">Tipo de evento</label><select id="n-ev" name="event_type"<?= $aria('event_type') ?>><option value="">Elige</option><?php foreach (config('app.booking.event_types') as $k => $label): ?><option value="<?= e($k) ?>"<?= ($old['event_type'] ?? '') === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select><?= $err('event_type') ?></div>
        <div class="f full"><label for="n-addr">Dirección (si es a domicilio)</label><input id="n-addr" name="address" maxlength="255" value="<?= $v('address') ?>"<?= $aria('address') ?>><?= $err('address') ?></div>
        <div class="f full"><label for="n-notes">Nota</label><textarea id="n-notes" name="notes" maxlength="1000" rows="2"><?= $v('notes') ?></textarea></div>
      </div>
    </fieldset>
    <label class="ckb"><input type="checkbox" name="deposit_received" value="1"<?= !empty($old['deposit_received']) ? ' checked' : '' ?>><span>El abono del 50 % ya se recibió (la reserva queda confirmada).</span></label>
    <div>
      <label class="ckb"><input type="checkbox" name="consent" value="1" required<?= !empty($old['consent']) ? ' checked' : '' ?><?= $aria('consent') ?>><span>La clienta autorizó el tratamiento de sus datos (Ley 1581 de 2012).</span></label>
      <?= $err('consent') ?>
    </div>
    <div class="nb-actions"><a class="btn ghost" href="/panel/reservas">Cancelar</a><button class="btn" type="submit">Registrar reserva</button></div>
  </form>

  <aside class="card stack nb-info" aria-label="Cómo se decide el origen">
    <h2 class="d">Origen y atribución</h2>
    <p class="small">El sistema decide el origen y no se puede cambiar después:</p>
    <ul class="small nb-list">
      <li><strong>Con código WEB-…</strong> válido: «WhatsApp desde la web», atribuida.</li>
      <li><strong>Teléfono con registro web</strong> en los últimos <?= e((string) config('app.attribution.window_months', 6)) ?> meses: «Clienta web recurrente», atribuida.</li>
      <li><strong>En otro caso</strong>: «Manual», sin comisión.</li>
    </ul>
    <p class="xs muted">Si eliges un origen manual para un teléfono que ya está atribuido a la web, la reserva se marca como recurrente y se avisa al socio.</p>
  </aside>
</div>
