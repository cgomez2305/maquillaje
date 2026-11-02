<?php
/**
 * Estudio y contacto (maqueta Estudio.dc.html).
 * @var list<array{0:string,1:?string}> $crumbs
 * @var array<string,string> $old
 * @var array<string,string> $errors
 * @var string|null $formError
 * @var array<string,string> $services
 * @var array<string,string> $places
 * @var string $minDate
 */
$b = config('app.business');
$mapQuery = rawurlencode('Make Up by Fletcher, ' . $b['address'] . ', ' . $b['city'] . ', ' . $b['region'] . ', Colombia');
$err = static function (string $field) use ($errors): string {
    return isset($errors[$field]) ? '<p class="err" id="' . e($field) . '-err">' . e($errors[$field]) . '</p>' : '';
};
$aria = static function (string $field) use ($errors): string {
    return isset($errors[$field]) ? ' aria-invalid="true" aria-describedby="' . e($field) . '-err"' : '';
};
$fcls = static fn(string $field): string => 'f' . (isset($errors[$field]) ? ' has-err' : '');
?>
<section class="wrap est-hero">
  <?= App\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
  <div class="g2 est-hero-grid">
    <div class="stack gap-40">
      <h1 class="hero-h1 est-h1">
        <span class="lbl in c-terracota">Estudio de maquillaje en Floridablanca</span>
        <span class="d in d1 est-title">Pasa por<br>el estudio.</span>
      </h1>
      <dl class="est-data in d3">
        <div><dt class="lbl c-topo">Dirección</dt><dd><?= e($b['address']) ?><br><?= e($b['city']) ?>, <?= e($b['region']) ?></dd></div>
        <div><dt class="lbl c-topo">Horario</dt><dd><span class="pending">[Días y horas de atención]</span><br><span class="est-sub">Solo con cita previa</span></dd></div>
        <div><dt class="lbl c-topo">WhatsApp</dt><dd><a href="<?= e(wa_href('estudio-datos', 'estudio')) ?>" rel="nofollow"><?= e($b['phone_display']) ?></a></dd></div>
        <div><dt class="lbl c-topo">Correo</dt><dd><a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a></dd></div>
      </dl>
    </div>
    <div class="ph px in d2 ar-45 arch-soft">
      <?= img('fotos/catalogo-brindis-estudio', 'María Paula Fletcher brindando con una clienta en el estudio de Floridablanca', ['priority' => true, 'class' => 'fill', 'sizes' => '(max-width: 960px) 100vw, 580px']) ?>
    </div>
  </div>
</section>

<section class="wrap sec-b" id="ubicacion">
  <div class="rv map">
    <svg class="map-art" viewBox="0 0 1240 460" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
      <g fill="none" stroke="#E2D6CC" stroke-width="18" stroke-linecap="round"><path d="M-20 120 C 300 160, 520 60, 1260 140"/><path d="M-20 360 C 380 300, 760 420, 1260 330"/><path d="M420 -20 C 460 160, 380 300, 470 480"/><path d="M880 -20 C 820 140, 900 320, 840 480"/></g>
      <g fill="none" stroke="#E9DFD6" stroke-width="6"><path d="M-20 240 L 1260 220"/><path d="M180 -20 L 220 480"/><path d="M640 -20 L 620 480"/><path d="M1060 -20 L 1080 480"/></g>
    </svg>
    <div class="map-pin" aria-hidden="true">
      <span class="ping"></span>
      <img src="<?= e(img_src('marca/isotipo-salmon', 120)) ?>" alt="" width="64" height="64">
    </div>
    <?php $mapSrc = 'https://www.google.com/maps?q=' . $mapQuery . '&z=16&output=embed'; ?>
    <iframe class="map-frame" title="Mapa: estudio Make Up by Fletcher en <?= e($b['address']) ?>, Floridablanca" data-src="<?= e($mapSrc) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    <noscript><iframe class="map-frame" title="Mapa: estudio Make Up by Fletcher en <?= e($b['address']) ?>, Floridablanca" src="<?= e($mapSrc) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></noscript>
    <a class="map-bar" href="<?= e($b['maps_url']) ?>" target="_blank" rel="noopener">
      <span>Make Up by Fletcher · <?= e($b['address']) ?>, Floridablanca</span>
      <span class="map-open">Abrir en Google Maps <?= icon('arrow', 'arr') ?></span>
    </a>
  </div>
</section>

<section class="bg-arena sec" id="cotizar">
  <div class="wrap g2 est-quote">
    <div class="rv stack gap-24">
      <p class="lbl c-terracota">Cotiza tu servicio</p>
      <h2 class="d d-l2">Cuéntanos<br>tu fecha.</h2>
      <p class="body-l est-quote-p">El mensaje se abre en WhatsApp ya redactado. Te respondemos con disponibilidad y precio.</p>
    </div>
    <form class="rv stack gap-40" method="post" action="/estudio-maquillaje-floridablanca/cotizar#cotizar" aria-label="Cotizar servicio de maquillaje" novalidate>
      <?= csrf_field() ?>
      <?= spam_fields() ?>
      <?php if (!empty($formError)): ?>
      <p class="notice err" role="alert"><?= e($formError) ?></p>
      <?php elseif ($errors): ?>
      <p class="notice err" role="alert">Revisa los campos marcados.</p>
      <?php endif; ?>
      <div class="fg">
        <div class="<?= $fcls('nombre') ?>"><label for="nombre">Nombre</label><input id="nombre" name="nombre" type="text" placeholder="Tu nombre" autocomplete="name" maxlength="120" required value="<?= e($old['nombre'] ?? '') ?>"<?= $aria('nombre') ?>><?= $err('nombre') ?></div>
        <div class="<?= $fcls('telefono') ?>"><label for="tel">Teléfono</label><input id="tel" name="telefono" type="tel" placeholder="300 000 0000" autocomplete="tel" inputmode="tel" maxlength="20" required value="<?= e($old['telefono'] ?? '') ?>"<?= $aria('telefono') ?>><?= $err('telefono') ?></div>
        <div class="<?= $fcls('fecha') ?>"><label for="fecha">Fecha del evento</label><input id="fecha" name="fecha" type="date" min="<?= e($minDate) ?>" value="<?= e($old['fecha'] ?? '') ?>"<?= $aria('fecha') ?>><?= $err('fecha') ?></div>
        <div class="<?= $fcls('hora') ?>"><label for="hora">Hora del evento</label><input id="hora" name="hora" type="time" value="<?= e($old['hora'] ?? '') ?>"<?= $aria('hora') ?>><?= $err('hora') ?></div>
        <div class="<?= $fcls('servicio') ?>"><label for="servicio">Servicio</label>
          <select id="servicio" name="servicio"<?= $aria('servicio') ?>>
            <?php foreach ($services as $k => $label): ?><option value="<?= e($k) ?>"<?= ($old['servicio'] ?? '') === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
          </select><?= $err('servicio') ?></div>
        <div class="<?= $fcls('lugar') ?>"><label for="lugar">Lugar</label>
          <select id="lugar" name="lugar"<?= $aria('lugar') ?>>
            <?php foreach ($places as $k => $label): ?><option value="<?= e($k) ?>"<?= ($old['lugar'] ?? '') === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
          </select><?= $err('lugar') ?></div>
      </div>
      <div class="<?= $fcls('mensaje') ?>"><label for="msg">Mensaje</label><textarea id="msg" name="mensaje" rows="3" maxlength="1000" placeholder="Hora del evento, número de personas, referencias de look"<?= $aria('mensaje') ?>><?= e($old['mensaje'] ?? '') ?></textarea><?= $err('mensaje') ?></div>
      <div class="stack gap-8">
        <label class="ckb"><input type="checkbox" name="autorizo" value="1" required<?= !empty($old['autorizo']) ? ' checked' : '' ?><?= $aria('autorizo') ?>> <span>Autorizo el tratamiento de mis datos para responder esta cotización, según la <a href="/politica-de-privacidad">política de tratamiento de datos</a> (Ley 1581 de 2012).</span></label>
        <?= isset($errors['autorizo']) ? '<p class="err est-err" id="autorizo-err">' . e($errors['autorizo']) . '</p>' : '' ?>
      </div>
      <div class="est-send">
        <button class="btn" type="submit">Enviar por WhatsApp <?= icon('arrow', 'arr') ?></button>
        <p class="est-note">Mínimo 2 días de anticipación · abono del 50 % por Nequi o Bancolombia.</p>
      </div>
    </form>
  </div>
</section>

<section class="wrap sec">
  <div class="rv stack gap-18 est-zones-head">
    <p class="lbl c-terracota">Zonas de servicio</p>
    <h2 class="d d-l">Todo el área<br>metropolitana.</h2>
  </div>
  <div class="rv est-zones">
    <?php foreach ([['Floridablanca', 'Estudio y domicilio'], ['Bucaramanga', 'A domicilio'], ['Piedecuesta', 'A domicilio'], ['Girón', 'A domicilio']] as [$city, $mode]): ?>
    <div><p class="d"><?= e($city) ?></p><p class="est-zone-mode"><?= e($mode) ?></p></div>
    <?php endforeach; ?>
  </div>
</section>
