<?php
/**
 * Reservar (maqueta Reservar.dc.html), pasos 1 a 3. El paso 4 es /reserva/{código}.
 * @var array $s estado de la reserva en sesión
 * @var array<string,string> $errors
 * @var array $packs
 * @var array{unit:int,total:int,deposit:int}|null $quote
 * @var DateTimeImmutable $monthStart
 * @var ?string $prevMonth
 * @var ?string $nextMonth
 * @var array<string,array{available:bool,reason:?string}> $days
 * @var list<string> $slots
 * @var list<string> $freeSlots
 */
$step = (int) $s['step'];
$labels = ['Servicio', 'Fecha', 'Datos', 'Listo'];
$tags = ['pack1' => 'Pack 01', 'pack2' => 'Pack 02 · A domicilio', 'pack3' => 'Pack 03'];
$descs = [
    'pack1' => 'Asesoría previa, maquillaje social y pestañas postizas. En el estudio.',
    'pack2' => 'Skin care, silla y luz de estudio en tu casa, pestañas y kit de retoque.',
    'pack3' => 'Prueba previa con dos propuestas, skin care, video reel y extra fijación.',
];
$names = ['pack1' => 'Maquillaje social', 'pack2' => 'Full experience', 'pack3' => 'Novias y quinceañeras'];
$err = static fn(string $f): string => isset($errors[$f]) ? '<p class="err" id="e-' . e($f) . '">' . e($errors[$f]) . '</p>' : '';
$aria = static fn(string $f): string => isset($errors[$f]) ? ' aria-invalid="true" aria-describedby="e-' . e($f) . '"' : '';
$d = $s['details'] ?? [];
$dateObj = !empty($s['date']) ? DateTimeImmutable::createFromFormat('!Y-m-d', (string) $s['date']) : null;
$timeLabel = static function (string $hhmm): string {
    return hora(new DateTimeImmutable('2000-01-01 ' . $hhmm));
};
?>
<section class="wrap rsv">
  <h1 class="rsv-head">
    <span class="lbl c-terracota in">Reservar cita · Make Up by Fletcher</span>
    <span class="d rsv-title in d1">Separa tu fecha.</span>
  </h1>
  <p class="rsv-intro in d2">Tres pasos y te enviamos el código de tu reserva. La fecha queda confirmada cuando recibimos el abono del 50 %.</p>

  <div class="rsv-layout">
    <form class="rsv-form" method="post" action="/reservar" novalidate data-reservar data-step="<?= e((string) $step) ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="step" value="<?= e((string) $step) ?>">

      <ol class="rsv-steps" aria-label="Progreso">
        <?php foreach ($labels as $i => $label): ?>
        <li class="rsv-st<?= $i + 1 <= $step ? ' on' : '' ?>"<?= $i + 1 === $step ? ' aria-current="step"' : '' ?>><span class="bar" aria-hidden="true"><span></span></span><span class="t">0<?= $i + 1 ?> · <?= e($label) ?></span></li>
        <?php endforeach; ?>
      </ol>

      <?php if ($errors !== []): ?>
      <div class="notice err rsv-alert" role="alert" tabindex="-1" data-focus-on-load>
        <?= isset($errors['form']) ? e($errors['form']) : 'Revisa los campos marcados para continuar.' ?>
      </div>
      <?php endif; ?>

      <?php if ($step === 1): ?>
      <section class="pane" aria-labelledby="t1">
        <h2 id="t1" class="d rsv-h2">¿Qué servicio necesitas?</h2>
        <fieldset class="rsv-opts">
          <legend class="sr-only">Servicio</legend>
          <?php foreach ($packs as $code => $p): ?>
          <label class="opt">
            <input class="sr-only" type="radio" name="service" value="<?= e($code) ?>" data-price="<?= e((string) $p['price']) ?>" data-name="<?= e($names[$code]) ?>"<?= $s['service'] === $code ? ' checked' : '' ?><?= $aria('service') ?>>
            <span class="opt-main"><span class="lbl c-terracota"><?= e($tags[$code]) ?></span><span class="d opt-name"><?= e($names[$code]) ?></span></span>
            <span class="opt-price"><?= e(money((int) $p['price'])) ?></span>
            <span class="opt-desc"><?= e($descs[$code]) ?></span>
          </label>
          <?php endforeach; ?>
        </fieldset>
        <?= $err('service') ?>
        <fieldset class="rsv-group">
          <legend class="rsv-q">¿Cuántas personas se maquillan?</legend>
          <div class="slots">
            <?php foreach ([1, 2, 3, 4] as $n): ?>
            <label class="slot"><input class="sr-only" type="radio" name="people" value="<?= $n ?>" data-label="<?= $n === 4 ? '4 o más' : $n ?>"<?= (int) $s['people'] === $n ? ' checked' : '' ?>><span><?= $n === 4 ? '4 o más' : $n ?></span></label>
            <?php endforeach; ?>
          </div>
          <p class="rsv-hint">Para cortejos de más de 4 personas, cuéntanos en la nota o escríbenos por WhatsApp.</p>
          <?= $err('people') ?>
        </fieldset>
      </section>

      <?php elseif ($step === 2): ?>
      <section class="pane" aria-labelledby="t2" id="calendario">
        <div class="rsv-cal-head">
          <h2 id="t2" class="d rsv-h2">Elige el día</h2>
          <div class="rsv-month">
            <?php if ($prevMonth): ?><button class="cal-nav" type="submit" name="mes" value="<?= e($prevMonth) ?>" formnovalidate aria-label="Mes anterior"><?= icon('chevron-left') ?></button><?php endif; ?>
            <p aria-live="polite"><?= e(ucfirst(mes_nombre((int) $monthStart->format('n'))) . ' ' . $monthStart->format('Y')) ?></p>
            <?php if ($nextMonth): ?><button class="cal-nav" type="submit" name="mes" value="<?= e($nextMonth) ?>" formnovalidate aria-label="Mes siguiente"><?= icon('chevron') ?></button><?php endif; ?>
          </div>
        </div>
        <fieldset>
          <legend class="sr-only">Día de <?= e(mes_nombre((int) $monthStart->format('n')) . ' de ' . $monthStart->format('Y')) ?></legend>
          <div class="cal">
            <?php foreach (['Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'] as $h): ?><span class="h" aria-hidden="true"><?= $h ?></span><?php endforeach; ?>
            <?php for ($i = 1; $i < (int) $monthStart->format('N'); $i++): ?><span class="day blank" aria-hidden="true"></span><?php endfor; ?>
            <?php foreach ($days as $ymd => $info):
                $dn = (int) substr($ymd, 8, 2);
                $label = $dn . ' de ' . mes_nombre((int) substr($ymd, 5, 2)) . ($info['available'] ? '' : ', no disponible'); ?>
            <label class="day<?= $info['available'] ? '' : ' off' ?>">
              <input class="sr-only" type="radio" name="date" value="<?= e($ymd) ?>" aria-label="<?= e($label) ?>" data-label="<?= e(fecha_larga(new DateTimeImmutable($ymd), false)) ?>"<?= $info['available'] ? '' : ' disabled' ?><?= ($s['date'] ?? '') === $ymd ? ' checked' : '' ?>>
              <span aria-hidden="true"><?= $dn ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </fieldset>
        <?= $err('date') ?>
        <p class="rsv-hint">Agenda con mínimo <?= (int) config('app.booking.min_days_ahead', 2) ?> días de anticipación. Los días tachados ya están ocupados o son festivos.</p>

        <fieldset class="rsv-group">
          <legend class="rsv-q">Hora de inicio del maquillaje</legend>
          <div class="slots" data-slots>
            <?php foreach ($slots as $t): $free = in_array($t, $freeSlots, true); ?>
            <label class="slot<?= $free ? '' : ' off' ?>"><input class="sr-only" type="radio" name="time" value="<?= e($t) ?>" data-label="<?= e($timeLabel($t)) ?>"<?= ($s['time'] ?? '') === $t ? ' checked' : '' ?><?= $free ? '' : ' disabled' ?>><span><?= e($timeLabel($t)) ?></span></label>
            <?php endforeach; ?>
          </div>
          <?= $err('time') ?>
        </fieldset>

        <fieldset class="rsv-group">
          <legend class="rsv-q">¿Dónde?</legend>
          <div class="slots">
            <label class="slot"><input class="sr-only" type="radio" name="location" value="studio" data-label="Estudio, Floridablanca"<?= ($s['location'] ?? 'studio') === 'studio' ? ' checked' : '' ?>><span>Estudio, Floridablanca</span></label>
            <label class="slot"><input class="sr-only" type="radio" name="location" value="home" data-label="A domicilio"<?= ($s['location'] ?? '') === 'home' ? ' checked' : '' ?>><span>A domicilio</span></label>
          </div>
          <p class="rsv-hint">A domicilio en <?php $z = config('app.business.area_served'); echo e(implode(', ', array_slice($z, 0, -1)) . ' y ' . end($z)); ?>, con el Pack 2 o el Pack 3.</p>
          <?= $err('location') ?>
        </fieldset>
      </section>

      <?php else: ?>
      <section class="pane" aria-labelledby="t3">
        <h2 id="t3" class="d rsv-h2">Tus datos</h2>
        <div class="fg">
          <div class="f<?= isset($errors['name']) ? ' has-err' : '' ?>"><label for="r-nombre">Nombre completo</label><input id="r-nombre" name="name" type="text" autocomplete="name" placeholder="Tu nombre" required maxlength="120" value="<?= e($d['name'] ?? '') ?>"<?= $aria('name') ?>><?= $err('name') ?></div>
          <div class="f<?= isset($errors['phone']) ? ' has-err' : '' ?>"><label for="r-tel">WhatsApp</label><input id="r-tel" name="phone" type="tel" autocomplete="tel" inputmode="tel" placeholder="300 000 0000" required maxlength="30" value="<?= e($d['phone'] ?? '') ?>"<?= $aria('phone') ?>><?= $err('phone') ?></div>
          <div class="f<?= isset($errors['email']) ? ' has-err' : '' ?>"><label for="r-correo">Correo</label><input id="r-correo" name="email" type="email" autocomplete="email" placeholder="tu@correo.com" required maxlength="190" value="<?= e($d['email'] ?? '') ?>"<?= $aria('email') ?>><?= $err('email') ?></div>
          <div class="f<?= isset($errors['birthday']) ? ' has-err' : '' ?>"><label for="r-cumple">Fecha de cumpleaños</label><input id="r-cumple" name="birthday" type="date" autocomplete="bday" max="<?= e(gmdate('Y-m-d')) ?>" value="<?= e($d['birthday'] ?? '') ?>"<?= $aria('birthday') ?>><?= $err('birthday') ?></div>
          <div class="f full<?= isset($errors['address']) ? ' has-err' : '' ?>"><label for="r-dir">Dirección del servicio</label><input id="r-dir" name="address" type="text" autocomplete="street-address" maxlength="255" placeholder="<?= ($s['location'] ?? '') === 'home' ? 'Dirección, barrio y ciudad' : 'Solo si es a domicilio' ?>" value="<?= e($d['address'] ?? '') ?>"<?= ($s['location'] ?? '') === 'home' ? ' required' : '' ?><?= $aria('address') ?>><?= $err('address') ?></div>
          <div class="f"><label for="r-origen">¿Cómo nos conociste?</label><select id="r-origen" name="how_found"><option value="">Elige una opción</option><?php foreach (config('app.booking.how_found') as $k => $v): ?><option value="<?= e($k) ?>"<?= ($d['how_found'] ?? '') === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></div>
          <div class="f<?= isset($errors['event_type']) ? ' has-err' : '' ?>"><label for="r-evento">Tipo de evento</label><select id="r-evento" name="event_type" required<?= $aria('event_type') ?>><option value="">Elige una opción</option><?php foreach (config('app.booking.event_types') as $k => $v): ?><option value="<?= e($k) ?>"<?= ($d['event_type'] ?? '') === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select><?= $err('event_type') ?></div>
          <div class="f full<?= isset($errors['notes']) ? ' has-err' : '' ?>"><label for="r-nota">Algo que debamos saber</label><textarea id="r-nota" name="notes" rows="2" maxlength="1000" placeholder="Referencias de look, alergias a productos, hora del evento"<?= $aria('notes') ?>><?= e($d['notes'] ?? '') ?></textarea><?= $err('notes') ?></div>
          <div class="full">
            <label class="ckb"><input type="checkbox" name="consent" value="1" required<?= !empty($d['consent']) ? ' checked' : '' ?><?= $aria('consent') ?>><span>Autorizo el tratamiento de mis datos para gestionar la reserva, según la <a href="/politica-de-privacidad" target="_blank">política de privacidad</a> (Ley 1581 de 2012).</span></label>
            <?= $err('consent') ?>
          </div>
          <label class="ckb full"><input type="checkbox" name="marketing" value="1"<?= !empty($d['marketing']) ? ' checked' : '' ?>><span>Quiero recibir recordatorios y novedades por correo.</span></label>
        </div>
        <?= spam_fields() ?>
        <p class="rsv-hint">Al enviar aceptas los <a href="/terminos" target="_blank">términos de reserva</a>: el abono del 50 % no es reembolsable, pero puedes reprogramar avisando con un día de anticipación.</p>
      </section>
      <?php endif; ?>

      <div class="rsv-nav">
        <button type="submit" class="btn ghost" name="action" value="back" formnovalidate<?= $step === 1 ? ' disabled' : '' ?>>Atrás</button>
        <button type="submit" class="btn" name="action" value="next"><?= $step === 3 ? 'Enviar reserva' : 'Continuar' ?> <?= icon('arrow', 'arr') ?></button>
      </div>
    </form>

    <aside class="sum" aria-label="Resumen de tu reserva" aria-live="polite">
      <p class="lbl c-topo">Tu reserva</p>
      <p class="d sum-name" data-sum="service"><?= e($names[$s['service']] ?? 'Elige un servicio') ?></p>
      <div class="row"><span>Personas</span><span data-sum="people"><?= (int) $s['people'] === 4 ? '4 o más' : e((string) $s['people']) ?></span></div>
      <div class="row"><span>Fecha</span><span data-sum="date"><?= $dateObj ? e(fecha_larga($dateObj, false)) : '—' ?></span></div>
      <div class="row"><span>Hora</span><span data-sum="time"><?= !empty($s['time']) ? e($timeLabel((string) $s['time'])) : '—' ?></span></div>
      <div class="row"><span>Lugar</span><span data-sum="location"><?= ($s['location'] ?? 'studio') === 'home' ? 'A domicilio' : 'Estudio, Floridablanca' ?></span></div>
      <hr class="hr">
      <div class="row total"><span>Total</span><span data-sum="total"><?= $quote ? e(money($quote['total'])) : '—' ?></span></div>
      <div class="row"><span>Abono para separar (50 %)</span><span data-sum="deposit"><?= $quote ? e(money($quote['deposit'])) : '—' ?></span></div>
      <p class="sum-note">El abono no es reembolsable, pero puedes reprogramar avisando con un día de anticipación.</p>
    </aside>
  </div>
</section>
