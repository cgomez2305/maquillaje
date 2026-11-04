<?php
/**
 * Reservar: solicitud por WhatsApp (sin pagos en línea ni datos guardados).
 * @var array<string,string> $old
 * @var array<string,string> $errors
 * @var array<string,array{name:string,price:int}> $packs
 * @var string $minDate
 * @var string $maxDate
 */
$tags = ['pack1' => 'Pack 01', 'pack2' => 'Pack 02 · A domicilio', 'pack3' => 'Pack 03'];
$names = ['pack1' => 'Maquillaje social', 'pack2' => 'Full experience', 'pack3' => 'Novias y quinceañeras'];
$descs = [
    'pack1' => 'Asesoría previa, maquillaje social y pestañas postizas. En el estudio.',
    'pack2' => 'Skin care, silla y luz de estudio en tu casa, pestañas y kit de retoque.',
    'pack3' => 'Prueba previa con dos propuestas, skin care, video reel y extra fijación.',
];
$maxPeople = (int) config('app.booking.max_people', 4);
$err = static fn(string $f): string => isset($errors[$f]) ? '<p class="err" id="e-' . e($f) . '">' . e($errors[$f]) . '</p>' : '';
$aria = static fn(string $f): string => isset($errors[$f]) ? ' aria-invalid="true" aria-describedby="e-' . e($f) . '"' : '';
$zones = config('app.business.area_served');
$price = isset($packs[$old['service']]) ? (int) $packs[$old['service']]['price'] : 0;
$total = $price * max(1, min($maxPeople, (int) $old['people']));
?>
<section class="wrap rsv">
  <h1 class="rsv-head">
    <span class="lbl c-terracota in">Reservar cita · Make Up by Fletcher</span>
    <span class="d rsv-title in d1">Separa tu fecha.</span>
  </h1>
  <p class="rsv-intro in d2">Elige tu servicio y la fecha: te abrimos WhatsApp con la solicitud ya escrita. Allí confirmamos la disponibilidad y la fecha queda separada con un abono del <?= (int) config('app.booking.deposit_percent', 50) ?> %.</p>

  <div class="rsv-layout">
    <form class="rsv-form" id="paso" method="post" action="/reservar" novalidate data-reservar>
      <?= csrf_field() ?>

      <?php if ($errors !== []): ?>
      <div class="notice err rsv-alert" role="alert" tabindex="-1" data-focus-on-load>Revisa los campos marcados para enviar tu solicitud.</div>
      <?php endif; ?>

      <fieldset class="rsv-block">
        <legend class="rsv-h2 d"><span class="rsv-n" aria-hidden="true">01</span> ¿Qué servicio necesitas?</legend>
        <div class="rsv-opts">
          <?php foreach ($packs as $code => $p): ?>
          <label class="opt">
            <input class="sr-only" type="radio" name="service" value="<?= e($code) ?>" data-price="<?= e((string) $p['price']) ?>" data-name="<?= e($names[$code]) ?>"<?= $old['service'] === $code ? ' checked' : '' ?><?= $aria('service') ?>>
            <span class="opt-main"><span class="lbl c-terracota"><?= e($tags[$code]) ?></span><span class="d opt-name"><?= e($names[$code]) ?></span></span>
            <span class="opt-price"><?= e(money((int) $p['price'])) ?></span>
            <span class="opt-desc"><?= e($descs[$code]) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
        <?= $err('service') ?>
      </fieldset>

      <fieldset class="rsv-block">
        <legend class="rsv-h2 d"><span class="rsv-n" aria-hidden="true">02</span> ¿Cuántas personas?</legend>
        <div class="slots">
          <?php for ($n = 1; $n <= $maxPeople; $n++): ?>
          <label class="slot"><input class="sr-only" type="radio" name="people" value="<?= $n ?>" data-label="<?= $n === $maxPeople ? $n . ' o más' : $n ?>"<?= (int) $old['people'] === $n ? ' checked' : '' ?>><span><?= $n === $maxPeople ? $n . ' o más' : $n ?></span></label>
          <?php endfor; ?>
        </div>
        <?= $err('people') ?>
      </fieldset>

      <fieldset class="rsv-block">
        <legend class="rsv-h2 d"><span class="rsv-n" aria-hidden="true">03</span> ¿Cuándo?</legend>
        <div class="f rsv-date<?= isset($errors['date']) ? ' has-err' : '' ?>">
          <label for="r-fecha">Fecha del evento</label>
          <input id="r-fecha" name="date" type="date" min="<?= e($minDate) ?>" max="<?= e($maxDate) ?>" value="<?= e($old['date']) ?>" required<?= $aria('date') ?>>
          <?= $err('date') ?>
        </div>
        <p class="rsv-q" id="hora-lbl">Hora de inicio del maquillaje</p>
        <div class="slots" role="radiogroup" aria-labelledby="hora-lbl">
          <label class="slot"><input class="sr-only" type="radio" name="time" value="" data-label="Por definir"<?= $old['time'] === '' ? ' checked' : '' ?>><span>Por definir</span></label>
          <?php foreach ((array) config('app.booking.slots') as $t): $lbl = hora(new DateTimeImmutable('2000-01-01 ' . $t)); ?>
          <label class="slot"><input class="sr-only" type="radio" name="time" value="<?= e($t) ?>" data-label="<?= e($lbl) ?>"<?= $old['time'] === $t ? ' checked' : '' ?>><span><?= e($lbl) ?></span></label>
          <?php endforeach; ?>
        </div>
        <?= $err('time') ?>
        <p class="rsv-hint">Agendamos con mínimo <?= (int) config('app.booking.min_days_ahead', 2) ?> días de anticipación. Para novias, la hora se ajusta a la ceremonia.</p>
      </fieldset>

      <fieldset class="rsv-block">
        <legend class="rsv-h2 d"><span class="rsv-n" aria-hidden="true">04</span> ¿Dónde?</legend>
        <div class="slots">
          <label class="slot"><input class="sr-only" type="radio" name="location" value="studio" data-label="Estudio, Floridablanca"<?= $old['location'] !== 'home' ? ' checked' : '' ?>><span><?= icon('pin') ?> Estudio, Floridablanca</span></label>
          <label class="slot"><input class="sr-only" type="radio" name="location" value="home" data-label="A domicilio"<?= $old['location'] === 'home' ? ' checked' : '' ?>><span><?= icon('home') ?> A domicilio</span></label>
        </div>
        <div class="f rsv-zone<?= isset($errors['zone']) ? ' has-err' : '' ?>">
          <label for="r-zona">Barrio y ciudad del servicio</label>
          <input id="r-zona" name="zone" type="text" maxlength="80" autocomplete="address-level2" placeholder="Ej.: Cabecera, Bucaramanga" value="<?= e($old['zone']) ?>"<?= $aria('zone') ?>>
          <?= $err('zone') ?>
          <p class="hint">A domicilio en <?= e(implode(', ', array_slice($zones, 0, -1)) . ' y ' . end($zones)) ?>.</p>
        </div>
      </fieldset>

      <fieldset class="rsv-block">
        <legend class="rsv-h2 d"><span class="rsv-n" aria-hidden="true">05</span> Sobre ti</legend>
        <div class="fg">
          <div class="f<?= isset($errors['name']) ? ' has-err' : '' ?>"><label for="r-nombre">Tu nombre</label><input id="r-nombre" name="name" type="text" autocomplete="given-name" placeholder="Tu nombre" required maxlength="80" value="<?= e($old['name']) ?>"<?= $aria('name') ?>><?= $err('name') ?></div>
          <div class="f<?= isset($errors['event']) ? ' has-err' : '' ?>"><label for="r-evento">Tipo de evento</label><select id="r-evento" name="event"<?= $aria('event') ?>><option value="">Elige una opción</option><?php foreach (config('app.booking.event_types') as $k => $v): ?><option value="<?= e($k) ?>"<?= $old['event'] === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select><?= $err('event') ?></div>
          <div class="f full"><label for="r-nota">Algo que debamos saber <span class="c-topo">(opcional)</span></label><textarea id="r-nota" name="notes" rows="2" maxlength="400" placeholder="Referencias de look, alergias, hora de la ceremonia"><?= e($old['notes']) ?></textarea></div>
        </div>
        <p class="rsv-hint">Tus datos no se guardan en la web: van solo en el mensaje que tú envías por WhatsApp.</p>
      </fieldset>

      <div class="rsv-nav">
        <p class="rsv-mini"><span>Total estimado</span><strong data-sum="total"><?= $price ? e(money($total)) : '—' ?></strong></p>
        <button type="submit" class="btn rsv-send"><?= icon('whatsapp') ?> Enviar por WhatsApp</button>
      </div>
    </form>

    <aside class="sum" aria-label="Así llegará tu mensaje">
      <p class="lbl c-topo">Así llegará tu mensaje</p>
      <div class="bubble" data-preview aria-live="polite">
        <p>Hola, Make Up by Fletcher. Quiero solicitar un servicio:</p>
        <p data-sum="lines">Elige el servicio, la fecha y el lugar.</p>
        <p>¿Tienen disponibilidad?</p>
      </div>
      <hr class="hr">
      <div class="row total"><span>Total estimado</span><span data-sum="total"><?= $price ? e(money($total)) : '—' ?></span></div>
      <div class="row"><span>Abono para separar (<?= (int) config('app.booking.deposit_percent', 50) ?> %)</span><span data-sum="deposit"><?= $price ? e(money(intdiv($total * (int) config('app.booking.deposit_percent', 50), 100))) : '—' ?></span></div>
      <p class="sum-note">El abono se envía por Nequi o Bancolombia cuando confirmemos la fecha. No es reembolsable, pero puedes reprogramar avisando con <?= (int) config('app.booking.change_notice_days', 1) ?> día de anticipación.</p>
    </aside>
  </div>
</section>
