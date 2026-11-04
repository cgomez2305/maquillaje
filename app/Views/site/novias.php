<?php
/**
 * Novias y quinceañeras (maqueta Novias.dc.html).
 * @var array<string,array{name:string,short:string,price:int,includes:list<string>}> $packs
 * @var list<array{q:string,a:string,pending?:string}> $faq
 * @var list<array{0:string,1:?string}> $crumbs
 */
?>
<section class="wrap nv-hero">
  <?= App\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
  <div class="g2 nv-hero-grid">
    <h1 class="nv-h1">
      <span class="lbl in c-terracota">Maquillaje de novia en Bucaramanga y Floridablanca</span>
      <span class="d nv-hero-d in d1">Tu versión<br>del sí.</span>
    </h1>
    <div class="stack gap-28 nv-hero-side in d2">
      <p class="lead">Para novias y quinceañeras que quieren reconocerse en las fotos dentro de veinte años. Prueba previa, dos propuestas y fijación extra para todo el día.</p>
      <div class="nv-prices">
        <p class="nv-price"><span class="d"><?= e(money($packs['pack3']['price'], false)) ?></span><span class="c-topo">COP · Novia o quinceañera</span></p>
        <a class="nv-price-alt" href="#cortejo"><span>Mamá, damas e invitadas desde <strong><?= e(money($packs['pack1']['price'])) ?></strong></span><?= icon('arrow', 'arr') ?></a>
      </div>
      <div class="wrap-row">
        <a class="btn" href="<?= e(wa_href('novias-hero', 'novias')) ?>" rel="nofollow">Consultar mi fecha</a>
        <a class="btn ghost" href="#incluye">Qué incluye</a>
      </div>
    </div>
  </div>
</section>

<section class="nv-cover-wrap">
  <div class="ph px in d3 nv-cover">
    <?= img('fotos/novia-labios-espejo', 'Novia con tiara y peinado de ondas sonriendo mientras le aplican el labial frente al espejo', ['priority' => true, 'sizes' => '100vw', 'class' => 'fill']) ?>
  </div>
</section>

<section id="incluye" class="bg-tinta sec">
  <div class="wrap">
    <div class="g2 nv-inc-head">
      <div class="rv stack gap-18">
        <p class="lbl c-salmon" id="quinceaneras">Pack 3 · Novias y quinceañeras</p>
        <h2 class="d d-l">Lo que incluye.</h2>
      </div>
      <p class="rv nv-on-dark mw-480">Todo pensado para que el día de la boda o de los quince no haya sorpresas: el maquillaje ya lo probaste, lo aprobaste y sabemos cómo se comporta con tu piel.</p>
    </div>
    <div class="g4 nv-inc-grid">
      <?php
      $inc = [
          ['01', 'Prueba previa', 'Probamos el maquillaje con calma antes del gran día, con la luz del estudio.'],
          ['02', 'Dos propuestas', 'Dos looks pensados para tu vestido, tu peinado y la hora del evento.'],
          ['03', 'Video reel', 'Grabamos el proceso para que te quede el recuerdo de la preparación.'],
          ['04', 'Extra fijación', 'Sellado reforzado para lágrimas, abrazos, calor y pista de baile.'],
      ];
      foreach ($inc as $i => [$n, $title, $text]): ?>
      <div class="inc rv" data-delay="<?= e((string) ($i * 0.06)) ?>">
        <p class="d inc-n" aria-hidden="true"><?= e($n) ?></p>
        <h3><?= e($title) ?></h3>
        <p><?= e($text) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <details class="rv nv-inc-more">
      <summary><span>Ver todo lo que incluye el Pack 3</span><?= icon('plus', 'pl') ?></summary>
      <p>También incluye asesoría previa, sesión de relajación con skin care (mascarilla húmeda, masaje facial, esencias aromáticas y masaje de manos con cera tibia), maquillaje el día del evento en silla profesional con luz de estudio, pestañas postizas y kit de retoque con papel de arroz, brillo labial y espejo de bolsillo.</p>
    </details>
    <div class="rv nv-inc-cta">
      <a class="btn light" href="/reservar?servicio=pack3">Reservar mi prueba</a>
      <a class="btn ghost-light" href="<?= e(wa_href('novias-incluye', 'novias')) ?>" rel="nofollow">Consultar mi fecha</a>
    </div>
  </div>
</section>

<section class="wrap sec">
  <div class="rv stack gap-18 nv-steps-head">
    <p class="lbl c-terracota">El proceso</p>
    <h2 class="d d-l big">Del primer mensaje<br>a la última foto.</h2>
  </div>
  <ol class="nv-steps">
    <?php
    $steps = [
        ['Paso 01', 'Separas tu fecha', 'Nos escribes por WhatsApp con la fecha, la hora y el lugar. La fecha queda reservada con un abono del 50 % por Nequi o Bancolombia.'],
        ['Paso 02', 'Prueba en el estudio', 'Unos días antes del evento analizamos tus rasgos, tu vestido y las fotos que sueñas. Probamos y ajustamos hasta que te veas tú.'],
        ['Paso 03', 'Eliges tu look', 'Te presentamos dos propuestas y eliges con tiempo, sin presión.'],
        ['Paso 04', 'El gran día', 'Skin care relajante, maquillaje en silla profesional con luz de estudio, extra fijación y el video reel de tu preparación. Te quedas con el kit de retoque.'],
    ];
    foreach ($steps as [$n, $title, $text]): ?>
    <li class="step rv">
      <p class="lbl c-terracota step-n"><?= e($n) ?></p>
      <h3 class="d step-t"><?= e($title) ?></h3>
      <p class="body-l step-p"><?= e($text) ?></p>
    </li>
    <?php endforeach; ?>
  </ol>
</section>

<section class="wrap sec-b" aria-labelledby="nv-gal-t">
  <div class="rv sec-head">
    <div>
      <p class="lbl c-terracota">Novias MUBF</p>
      <h2 class="d d-l" id="nv-gal-t">Reales, en su día.</h2>
    </div>
    <a class="btn ghost hide-m" href="/portafolio#novias">Ver más novias</a>
  </div>
  <ul class="g3 m-snap nv-gal" role="list">
    <?php foreach ([
        ['fotos/novia-tiara-ramo', 'Novia con tiara y recogido alto sonriendo con su ramo de hortensias'],
        ['fotos/novia-bata-ramo', 'Novia en bata de seda con ondas suaves y ramo de lirios blancos'],
        ['fotos/novia-cola-jardin', 'Novia con velo catedral en un jardín, de espaldas a la cámara'],
        ['fotos/novia-cortina-retrato', 'Novia con tiara y vestido de tirantes frente a una cortina violeta'],
        ['fotos/novia-pareja', 'Novia con tiara recibiendo un beso del novio en la frente durante la recepción'],
        ['fotos/novia-balcon', 'Novia con velo y vestido de seda en un balcón con vista a la ciudad'],
    ] as [$photo, $alt]): ?>
    <li class="rv"><div class="ph ar-34"><?= img($photo, $alt, ['sizes' => '(max-width: 960px) 80vw, 400px']) ?></div></li>
    <?php endforeach; ?>
  </ul>
  <p class="m-snap-hint only-m" aria-hidden="true">Desliza para ver más <?= icon('arrow', 'arr') ?></p>
</section>

<section class="wrap sec-b" id="cortejo">
  <div class="g2 nv-cortejo">
    <div class="ph px rv ar-45 m-43 arch-soft"><?= img('fotos/novia-madre', 'Novia y su mamá abrazadas en batas de seda blanca y rosa durante la preparación', ['sizes' => '(max-width: 960px) 100vw, 560px', 'class' => 'fill']) ?></div>
    <div class="stack gap-32">
      <p class="lbl rv c-terracota">Para el cortejo</p>
      <h2 class="d d-m rv">Mamá, damas<br>e invitadas.</h2>
      <p class="rv body-l mw-480">Una boda casi nunca es un solo maquillaje. Agenda a quien te acompaña el mismo día y mantén un solo estilo en todas las fotos.</p>
      <div class="rv price-list">
        <div class="price-row"><div><p class="name">Pack 1 · Servicio base</p><p class="detail">Asesoría, maquillaje social y pestañas</p></div><p class="amount"><?= e(money($packs['pack1']['price'])) ?></p></div>
        <div class="price-row"><div><p class="name">Pack 2 · Full experience</p><p class="detail">Skin care, silla y luz de estudio, kit de retoque</p></div><p class="amount"><?= e(money($packs['pack2']['price'])) ?></p></div>
      </div>
    </div>
  </div>
</section>

<section class="bg-arena sec">
  <div class="wrap g2 nv-season">
    <div class="rv stack gap-24">
      <p class="lbl c-terracota">Temporada de bodas</p>
      <p class="d nv-season-d">Octubre, noviembre y diciembre son los meses más pedidos. Las fechas de 2027 ya se están separando.</p>
    </div>
    <div class="rv nv-season-cta"><a class="btn" href="/reservar?servicio=pack3">Ver fechas disponibles</a></div>
  </div>
</section>

<section class="wrap sec">
  <div class="g2 nv-faq-grid">
    <div class="rv stack gap-18">
      <p class="lbl c-terracota">Preguntas de novias</p>
      <h2 class="d d-l2">Todo claro<br>antes del sí.</h2>
    </div>
    <div class="rv faq">
      <?php foreach ($faq as $i => $item): ?>
      <details<?= $i === 0 ? ' open' : '' ?>>
        <summary><span><?= e($item['q']) ?></span><?= icon('plus', 'pl') ?></summary>
        <div><p><?= e($item['a']) ?><?php if (!empty($item['pending'])): ?> <span class="pending"><?= e($item['pending']) ?></span><?php endif; ?></p></div>
      </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="wrap sec-b">
  <div class="rv cta mesh nv-cta">
    <div class="deco" aria-hidden="true"><img src="<?= e(img_src('marca/isotipo-nude', 360)) ?>" alt="" width="360" height="360" loading="lazy"></div>
    <p class="lbl">Novias 2027</p>
    <h2 class="d d-xl">Separa tu fecha.</h2>
    <div class="wrap-row cta-actions">
      <a class="btn" href="<?= e(wa_href('novias-cta', 'novias')) ?>" rel="nofollow">Escribir por WhatsApp <?= icon('arrow', 'arr') ?></a>
      <a class="btn ghost" href="/reservar">Reservar en línea</a>
    </div>
  </div>
</section>
