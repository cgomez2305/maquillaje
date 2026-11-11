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
        <span class="lbl c-terracota">Maquilladora en Floridablanca · estudio con cita</span>
        <span class="d est-title">Pasa por<br>el estudio.</span>
      </h1>
      <dl class="est-data">
        <div><dt class="lbl c-topo">Dirección</dt><dd><?= e($b['address']) ?><br><?= e($b['city']) ?>, <?= e($b['region']) ?></dd></div>
        <div><dt class="lbl c-topo">Horario</dt><dd>Solo con cita previa<br><span class="est-sub">Agenda por WhatsApp</span></dd></div>
        <div><dt class="lbl c-topo">WhatsApp</dt><dd><a href="<?= e(wa_href('estudio-datos', 'estudio')) ?>" rel="nofollow"><?= e($b['phone_display']) ?></a></dd></div>
        <div><dt class="lbl c-topo">Correo</dt><dd><a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a></dd></div>
      </dl>
      <div class="est-quick only-m">
        <a class="btn" href="#cotizar">Cotizar mi servicio</a>
        <a class="btn ghost" href="<?= e($b['maps_url']) ?>" target="_blank" rel="noopener">Cómo llegar</a>
      </div>
    </div>
    <div class="ph px ar-45 m-43 arch-soft">
      <?= img('fotos/catalogo-brindis-estudio', 'María Paula Fletcher brindando con una clienta en el estudio de Floridablanca', ['priority' => true, 'class' => 'fill', 'sizes' => '(max-width: 960px) 100vw, 580px']) ?>
    </div>
  </div>
</section>

<section class="collage" data-collage aria-labelledby="est-col-t">
  <?php foreach ([
      ['proceso-estudio-espejo', 'cl1', 30], ['catalogo-asesoria-estudio', 'cl2', 55], ['proceso-brocha-bn', 'cl3', 18],
      ['catalogo-brindis-estudio', 'cl4', 42], ['proceso-labios-perfil', 'cl5', 66],
  ] as [$photo, $cls, $speed]): ?>
  <div class="cl <?= $cls ?>" data-speed="<?= $speed ?>" aria-hidden="true"><div class="ph"><?= img('fotos/' . $photo, '', ['sizes' => '(max-width: 960px) 30vw, 240px', 'width' => 480]) ?></div></div>
  <?php endforeach; ?>
  <div class="wrap collage-c">
    <h2 class="d collage-t" id="est-col-t">Luz de estudio,<br>espejo de verdad.</h2>
    <p class="body-l">Lo que ves en el espejo es lo que sale en la foto. Silla profesional, luz pareja y el tiempo que tu piel necesita, en Floridablanca o en tu casa.</p>
  </div>
</section>

<section class="wrap sec-b" id="ubicacion">
  <div class="map">
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
    <div class="stack gap-24">
      <h2 class="d d-l2">Cuéntanos<br>tu fecha.</h2>
      <p class="body-l est-quote-p">Llena lo básico y el mensaje se abre en WhatsApp ya redactado. Te respondemos con disponibilidad y precio.</p>
    </div>
    <form class="stack gap-40" method="post" action="/estudio-maquillaje-floridablanca/cotizar#cotizar" aria-label="Cotizar servicio de maquillaje" novalidate>
      <?= csrf_field() ?>
      <?php if (!empty($formError)): ?>
      <p class="notice err" role="alert"><?= e($formError) ?></p>
      <?php elseif ($errors): ?>
      <p class="notice err" role="alert">Revisa los campos marcados.</p>
      <?php endif; ?>
      <div class="fg">
        <div class="<?= $fcls('nombre') ?>"><label for="nombre">Nombre</label><input id="nombre" name="nombre" type="text" placeholder="Tu nombre" autocomplete="given-name" maxlength="80" required value="<?= e($old['nombre'] ?? '') ?>"<?= $aria('nombre') ?>><?= $err('nombre') ?></div>
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
      <div class="<?= $fcls('mensaje') ?>"><label for="msg">Mensaje</label><textarea id="msg" name="mensaje" rows="3" maxlength="400" placeholder="Número de personas, referencias de look"<?= $aria('mensaje') ?>><?= e($old['mensaje'] ?? '') ?></textarea><?= $err('mensaje') ?></div>
      <div class="est-send">
        <button class="btn" type="submit"><?= icon('whatsapp') ?> Enviar por WhatsApp</button>
        <p class="est-note">Tus datos no se guardan en la web: van solo en el mensaje que tú envías. Mínimo 2 días de anticipación.</p>
      </div>
    </form>
  </div>
</section>

<section class="wrap sec">
  <div class="stack gap-18 est-zones-head">
    <h2 class="d d-l">Todo el área<br>metropolitana.</h2>
  </div>
  <div class="est-zones">
    <?php foreach ([['Floridablanca', 'Estudio y domicilio'], ['Bucaramanga', 'A domicilio'], ['Piedecuesta', 'A domicilio'], ['Girón', 'A domicilio']] as [$city, $mode]): ?>
    <div><p class="d"><?= e($city) ?></p><p class="est-zone-mode"><?= e($mode) ?></p></div>
    <?php endforeach; ?>
  </div>
</section>
