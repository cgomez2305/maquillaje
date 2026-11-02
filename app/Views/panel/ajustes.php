<?php
/**
 * @var array<string,string> $errors @var array $old @var list<string> $slots @var list<int> $closed
 * @var list<string> $openHolidays @var array<string,string> $holidays @var list<array> $blocks
 * @var list<array> $services @var list<array> $products
 */
use App\Controllers\Panel\AjustesController;
$err = static fn(string $f): string => isset($errors[$f]) ? '<p class="flash err" role="alert">' . e($errors[$f]) . '</p>' : '';
$fmt = static fn(string $utc): string => fecha_corta(local_time($utc)) . ' · ' . hora(local_time($utc));
$cats = ['service' => 'Servicio', 'course' => 'Curso', 'product' => 'Producto', 'gift' => 'Bono'];
?>
<div class="top">
  <div><p class="lbl">Solo la dueña</p><h1 class="d">Ajustes</h1></div>
  <nav class="chips" aria-label="Secciones">
    <a class="chip" href="#slots">Horarios</a><a class="chip" href="#weekdays">Días</a><a class="chip" href="#block_add">Bloqueos</a>
    <a class="chip" href="#services">Precios</a><a class="chip" href="#products">Tienda</a>
  </nav>
</div>
<?= $err('form') ?>

<div class="g2">
  <section class="card stack" id="slots" aria-labelledby="h-slots">
    <h2 class="d card-title" id="h-slots">Horas de inicio</h2>
    <p class="muted small">Las horas que se ofrecen en /reservar (formato 24 h). Cada cita dura <?= (int) config('app.booking.slot_minutes') ?> min, o <?= (int) config('app.booking.minutes_per_person') ?> min por persona si son varias.</p>
    <?= $err('slots') ?>
    <form method="post" action="/panel/ajustes" class="stack">
      <?= csrf_field() ?><input type="hidden" name="section" value="slots">
      <div class="f"><label for="aj-slots">Horas</label><input id="aj-slots" name="slots" value="<?= e($old['slots'] ?? implode(', ', $slots)) ?>" placeholder="05:00, 07:00, 09:00"></div>
      <button class="btn self-start" type="submit">Guardar horarios</button>
    </form>
  </section>

  <section class="card stack" id="weekdays" aria-labelledby="h-week">
    <h2 class="d card-title" id="h-week">Días cerrados</h2>
    <p class="muted small">Los días marcados no aparecen disponibles en el calendario de reservas.</p>
    <?= $err('weekdays') ?>
    <form method="post" action="/panel/ajustes" class="stack">
      <?= csrf_field() ?><input type="hidden" name="section" value="weekdays">
      <fieldset class="checks"><legend class="sr-only">Días de la semana cerrados</legend>
        <?php foreach (AjustesController::WEEKDAYS as $n => $name): ?>
        <label class="ckb"><input type="checkbox" name="closed[]" value="<?= $n ?>"<?= in_array($n, $closed, true) ? ' checked' : '' ?>> <?= e($name) ?></label>
        <?php endforeach; ?>
      </fieldset>
      <button class="btn self-start" type="submit">Guardar días</button>
    </form>
  </section>
</div>

<section class="card stack" id="holidays" aria-labelledby="h-hol">
  <h2 class="d card-title" id="h-hol">Festivos</h2>
  <p class="muted small">Los festivos de Colombia se bloquean solos. Marca los que sí vas a trabajar (temporada de bodas y grados).</p>
  <form method="post" action="/panel/ajustes" class="stack">
    <?= csrf_field() ?><input type="hidden" name="section" value="holidays">
    <fieldset class="checks checks-wide"><legend class="sr-only">Festivos que se trabajan</legend>
      <?php foreach ($holidays as $d => $name): ?>
      <label class="ckb"><input type="checkbox" name="open[]" value="<?= e($d) ?>"<?= in_array($d, $openHolidays, true) ? ' checked' : '' ?>> <span><?= e(fecha_corta(new DateTimeImmutable($d))) ?> · <span class="muted"><?= e($name) ?></span></span></label>
      <?php endforeach; ?>
    </fieldset>
    <button class="btn self-start" type="submit">Guardar festivos</button>
  </form>
</section>

<section class="card stack" id="block_add" aria-labelledby="h-blk">
  <h2 class="d card-title" id="h-blk">Bloqueos de agenda</h2>
  <p class="muted small">Vacaciones, cursos, eventos propios: el rango bloqueado no se puede reservar desde la web.</p>
  <?= $err('block') ?>
  <form method="post" action="/panel/ajustes" class="fg">
    <?= csrf_field() ?><input type="hidden" name="section" value="block_add">
    <div class="f"><label for="bl-date">Desde el día</label><input id="bl-date" type="date" name="date" required value="<?= e($old['date'] ?? '') ?>"></div>
    <div class="f"><label for="bl-date2">Hasta el día</label><input id="bl-date2" type="date" name="date_to" value="<?= e($old['date_to'] ?? '') ?>"><span class="hint">Vacío = el mismo día</span></div>
    <div class="f"><label for="bl-from">Hora inicial</label><input id="bl-from" type="time" name="from" value="<?= e($old['from'] ?? '08:00') ?>"></div>
    <div class="f"><label for="bl-to">Hora final</label><input id="bl-to" type="time" name="to" value="<?= e($old['to'] ?? '18:00') ?>"></div>
    <label class="ckb full"><input type="checkbox" name="all_day" value="1"<?= !empty($old['all_day']) ? ' checked' : '' ?>> Todo el día (ignora las horas)</label>
    <div class="f full"><label for="bl-reason">Motivo (interno)</label><input id="bl-reason" name="reason" maxlength="120" value="<?= e($old['reason'] ?? '') ?>" placeholder="Vacaciones, curso, evento…"></div>
    <div class="full"><button class="btn" type="submit">Bloquear</button></div>
  </form>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th scope="col">Desde</th><th scope="col">Hasta</th><th scope="col">Motivo</th><th scope="col"><span class="sr-only">Acciones</span></th></tr></thead>
      <tbody>
      <?php if ($blocks === []): ?><tr><td colspan="4" class="empty">No hay bloqueos próximos.</td></tr><?php endif; ?>
      <?php foreach ($blocks as $b): ?>
        <tr>
          <td class="nowrap"><?= e($fmt($b['starts_at'])) ?></td><td class="nowrap"><?= e($fmt($b['ends_at'])) ?></td><td><?= e($b['reason'] ?? '—') ?></td>
          <td class="n"><form method="post" action="/panel/ajustes" data-confirm="¿Quitar este bloqueo?"><?= csrf_field() ?><input type="hidden" name="section" value="block_del"><input type="hidden" name="id" value="<?= (int) $b['id'] ?>"><button class="btn ghost btn-sm" type="submit">Quitar</button></form></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<div class="g2">
  <section class="card stack" id="services" aria-labelledby="h-srv">
    <h2 class="d card-title" id="h-srv">Precios de servicios y cursos</h2>
    <p class="muted small">Un cambio de precio aplica a las reservas nuevas: cada reserva guarda el precio con el que se creó.</p>
    <?= $err('services') ?>
    <form method="post" action="/panel/ajustes" class="stack">
      <?= csrf_field() ?><input type="hidden" name="section" value="services">
      <div class="tbl-wrap"><table class="tbl">
        <thead><tr><th scope="col">Servicio</th><th scope="col" class="n">Precio (COP)</th><th scope="col" class="n">Activo</th></tr></thead>
        <tbody>
        <?php foreach ($services as $s): ?>
          <tr><td><label for="sp-<?= (int) $s['id'] ?>"><?= e($s['name']) ?></label><span class="muted xs block"><?= e($cats[$s['category']] ?? '') ?> · <?= e($s['code']) ?></span></td>
          <td class="n"><input class="in-num" id="sp-<?= (int) $s['id'] ?>" name="price[<?= (int) $s['id'] ?>]" inputmode="numeric" value="<?= (int) $s['price'] ?>"></td>
          <td class="n"><input type="checkbox" name="active[<?= (int) $s['id'] ?>]" value="1" aria-label="Activo: <?= e($s['code']) ?>"<?= (int) $s['active'] ? ' checked' : '' ?>></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <button class="btn self-start" type="submit">Guardar precios</button>
    </form>
  </section>

  <section class="card stack" id="products" aria-labelledby="h-prd">
    <h2 class="d card-title" id="h-prd">Tienda</h2>
    <p class="muted small">Para vender un kit en línea, ponle precio y actívalo. Sin precio no se puede activar.</p>
    <?= $err('products') ?>
    <form method="post" action="/panel/ajustes" class="stack">
      <?= csrf_field() ?><input type="hidden" name="section" value="products">
      <div class="tbl-wrap"><table class="tbl">
        <thead><tr><th scope="col">Producto</th><th scope="col" class="n">Precio (COP)</th><th scope="col" class="n">En venta</th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
          <tr><td><label for="pp-<?= (int) $p['id'] ?>"><?= e($p['name']) ?></label><span class="muted xs block"><?= e($cats[$p['category']] ?? '') ?> · <?= e($p['subcategory'] ?? '') ?></span></td>
          <td class="n"><input class="in-num" id="pp-<?= (int) $p['id'] ?>" name="price[<?= (int) $p['id'] ?>]" inputmode="numeric" value="<?= (int) $p['price'] ?>"></td>
          <td class="n"><input type="checkbox" name="active[<?= (int) $p['id'] ?>]" value="1" aria-label="En venta: <?= e($p['slug']) ?>"<?= (int) $p['active'] ? ' checked' : '' ?>></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
      <button class="btn self-start" type="submit">Guardar tienda</button>
    </form>
  </section>
</div>

<section class="card">
  <h2 class="d card-title">Tasas de comisión</h2>
  <p class="small muted">Las tasas no se editan aquí: se proponen y solo rigen cuando la dueña y el socio las aprueban. Cada reserva guarda la tasa vigente al crearse.</p>
  <p class="mt-10"><a class="btn ghost" href="/panel/comisiones">Ir a Comisiones</a></p>
</section>
