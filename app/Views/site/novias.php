<?php
/**
 * Novias (maqueta Novias.dc.html). Las quinceañeras tienen su propia página (/maquillaje-para-quinceaneras-bucaramanga).
 * @var array<string,array{name:string,short:string,price:int,includes:list<string>}> $packs
 * @var list<array{q:string,a:string,pending?:string}> $faq
 * @var list<array{0:string,1:?string}> $crumbs
 */
?>
<section class="wrap nv-hero">
  <?= App\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
  <div class="g2 nv-hero-grid">
    <h1 class="nv-h1">
      <span class="lbl c-terracota">Maquillaje de novia en Bucaramanga</span>
      <span class="d nv-hero-d">Tu versión<br>del sí.</span>
    </h1>
    <div class="stack gap-28 nv-hero-side">
      <p class="lead">Para novias que quieren reconocerse en las fotos dentro de veinte años. Prueba previa, dos propuestas y fijación extra para todo el día.</p>
      <div class="nv-prices">
        <p class="nv-price"><span class="d"><?= e(money($packs['pack3']['price'], false)) ?></span><span class="c-topo">COP · Pack 3 de novia</span></p>
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
  <div class="ph px nv-cover">
    <?= img('fotos/novia-labios-espejo', 'Novia con tiara y peinado de ondas sonriendo mientras le aplican el labial frente al espejo', ['priority' => true, 'sizes' => '100vw', 'class' => 'fill']) ?>
  </div>
</section>

<section id="incluye" class="bg-tinta sec">
  <div class="wrap">
    <div class="g2 nv-inc-head">
      <div class="stack gap-18">
        <h2 class="d d-l">Lo que incluye<br>el Pack 3.</h2>
      </div>
      <p class="nv-on-dark mw-480">Todo pensado para que el día de la boda no haya sorpresas: el maquillaje ya lo probaste, lo aprobaste y sabemos cómo se comporta con tu piel.</p>
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
      <div class="inc">
        <p class="d inc-n" aria-hidden="true"><?= e($n) ?></p>
        <h3><?= e($title) ?></h3>
        <p><?= e($text) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <details class="nv-inc-more">
      <summary><span>Ver todo lo que incluye el Pack 3</span><?= icon('plus', 'pl') ?></summary>
      <p>También incluye asesoría previa, sesión de relajación con skin care (mascarilla húmeda, masaje facial, esencias aromáticas y masaje de manos con cera tibia), maquillaje el día del evento en silla profesional con luz de estudio, pestañas postizas y kit de retoque con papel de arroz, brillo labial y espejo de bolsillo.</p>
    </details>
    <div class="nv-inc-cta">
      <a class="btn light" href="/reservar?servicio=pack3">Reservar mi prueba</a>
      <a class="btn ghost-light" href="<?= e(wa_href('novias-incluye', 'novias')) ?>" rel="nofollow">Consultar mi fecha</a>
    </div>
  </div>
</section>

<section class="wrap sec nv-fm" aria-labelledby="nv-fm-t">
  <div class="stack gap-18 nv-fm-head">
    <h2 class="d d-l" id="nv-fm-t">Así se ve el <span class="nowrap">Pack 3</span><br class="hide-m"> en tu piel.</h2>
  </div>
  <div class="fm" data-facemap>
    <figure class="fm-fig">
      <div class="ph fm-ph"><?= img('fotos/novia-rostro', 'Primer plano de una novia con tiara: piel luminosa, cejas definidas, pestañas y labios en tono rosado', ['sizes' => '(max-width: 960px) 100vw, 460px']) ?></div>
      <div aria-hidden="true">
        <div class="fm-pt l fm-1"><span class="fm-lbl">Cejas peinadas<small>definidas sin endurecer el rostro</small></span><span class="fm-line"></span><span class="fm-dot" data-n="1"></span></div>
        <div class="fm-pt r fm-2"><span class="fm-lbl">Pestañas postizas<small>incluidas en el Pack 3</small></span><span class="fm-line"></span><span class="fm-dot" data-n="2"></span></div>
        <div class="fm-pt l fm-3"><span class="fm-lbl">Piel satinada<small>luminosa en foto, hasta 12 horas</small></span><span class="fm-line"></span><span class="fm-dot" data-n="3"></span></div>
        <div class="fm-pt r fm-4"><span class="fm-lbl">Labio de larga duración<small>resiste brindis y besos</small></span><span class="fm-line"></span><span class="fm-dot" data-n="4"></span></div>
        <div class="fm-pt l fm-5"><span class="fm-lbl">Extra fijación<small>para lágrimas, abrazos y pista</small></span><span class="fm-line"></span><span class="fm-dot" data-n="5"></span></div>
      </div>
    </figure>
    <ol class="fm-list">
      <li><span>Cejas peinadas<small>definidas sin endurecer el rostro</small></span></li>
      <li><span>Pestañas postizas<small>incluidas en el Pack 3</small></span></li>
      <li><span>Piel satinada<small>luminosa en foto, hasta 12 horas</small></span></li>
      <li><span>Labio de larga duración<small>resiste brindis y besos</small></span></li>
      <li><span>Extra fijación<small>para lágrimas, abrazos y pista</small></span></li>
    </ol>
  </div>
</section>

<section class="sec deck-sec" aria-labelledby="nv-deck-t" data-deck>
  <div class="deck-pin">
    <div class="wrap stack gap-18 nv-steps-head">
      <h2 class="d d-l" id="nv-deck-t">Del primer mensaje<br>a la última foto.</h2>
    </div>
    <ol class="deck">
      <?php
      $steps = [
          ['01', 'Separas tu fecha', 'Nos escribes por WhatsApp con la fecha, la hora y el lugar. La fecha queda reservada con un abono del 50 %.', 'fotos/novia-puerta-vestido', 'Novia en bata con tiara frente a su vestido colgado en la puerta'],
          ['02', 'Prueba en el estudio', 'Analizamos tus rasgos, tu vestido y las fotos que sueñas. Probamos y ajustamos hasta que te veas tú.', 'fotos/proceso-brocha-bn', 'María Paula Fletcher maquillando a una novia durante la prueba'],
          ['03', 'Eliges tu look', 'Te presentamos dos propuestas y eliges con tiempo, sin presión.', 'fotos/novia-labios-espejo', 'Novia sonriendo frente al espejo mientras le aplican el labial'],
          ['04', 'El gran día', 'Skin care, maquillaje con luz de estudio, extra fijación y el video reel de tu preparación.', 'fotos/novia-pareja', 'Novia con tiara recibiendo un beso del novio durante la recepción'],
      ];
      foreach ($steps as $i => [$n, $title, $text, $photo, $alt]): ?>
      <li class="deck-card dc<?= $i + 1 ?>">
        <div class="ph deck-ph"><?= img($photo, $alt, ['sizes' => '(max-width: 960px) 96px, 300px']) ?></div>
        <div class="deck-body">
          <p class="deck-n"><span class="sr-only">Paso </span><?= e($n) ?></p>
          <h3 class="d deck-t"><?= e($title) ?></h3>
          <p class="deck-p"><?= e($text) ?></p>
        </div>
      </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>

<section class="wrap sec-b looks" aria-labelledby="nv-looks-t">
  <div class="stack gap-18 looks-head">
    <h2 class="d d-l" id="nv-looks-t">Natural, glam<br>o editorial.</h2>
    <p class="body-l mw-460">En la prueba previa te presentamos dos propuestas. Estas son las tres direcciones que más piden nuestras novias.</p>
  </div>
  <?php
  $looks = [
      ['natural', 'Natural', 'Piel luminosa que se ve como piel, tonos cálidos en ojos y mejillas y pestañas sutiles.', 'Ceremonias de día, jardín o playa, y novias que quieren reconocerse en cada foto.', 'fotos/novia-bata-ramo', 'Novia con maquillaje natural, ondas suaves y ramo de lirios'],
      ['glam', 'Glam', 'Mirada intensa con sombras doradas, piel pulida y labio definido que resiste toda la noche.', 'Fiestas de noche, salones con luz cálida y fotos con flash.', 'fotos/social-cumple-beso', 'Clienta con maquillaje glam de ojos ahumados dorados'],
      ['editorial', 'Editorial', 'Acabado limpio y sobrio, con luz en los puntos altos del rostro y un labio en tono piel.', 'Sesiones de fotos, vestidos minimalistas y bodas íntimas.', 'fotos/novia-calas-editorial', 'Novia con maquillaje editorial y velo entre calas blancas'],
  ];
  ?>
  <div class="looks-ui" data-tabs>
    <div class="looks-tabs" role="tablist" aria-label="Elige un look" hidden>
      <?php foreach ($looks as $i => [$id, $name, , , $photo]): ?>
      <button type="button" class="look-tab" role="tab" id="lt-<?= e($id) ?>" aria-controls="lp-<?= e($id) ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"<?= $i === 0 ? '' : ' tabindex="-1"' ?>>
        <span class="look-thumb"><?= img($photo, '', ['sizes' => '64px', 'width' => 480]) ?></span>
        <span class="look-name"><?= e($name) ?></span>
      </button>
      <?php endforeach; ?>
    </div>
    <?php foreach ($looks as $i => [$id, $name, $text, $for, $photo, $alt]): ?>
    <div class="look-panel" role="tabpanel" id="lp-<?= e($id) ?>" aria-labelledby="lt-<?= e($id) ?>">
      <div class="ph look-ph"><?= img($photo, $alt, ['sizes' => '(max-width: 960px) 100vw, 560px']) ?></div>
      <div class="look-txt">
        <h3 class="d look-title"><?= e($name) ?></h3>
        <p class="body-l"><?= e($text) ?></p>
        <p class="look-for"><span class="lbl c-topo">Ideal para</span><?= e($for) ?></p>
        <a class="btn self-start" href="/reservar?servicio=pack3">Reservar mi prueba <?= icon('arrow', 'arr') ?></a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="wrap sec-b" aria-labelledby="nv-gal-t">
  <div class="sec-head">
    <div>
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
    <li><div class="ph ar-34"><?= img($photo, $alt, ['sizes' => '(max-width: 960px) 80vw, 400px']) ?></div></li>
    <?php endforeach; ?>
  </ul>
  <p class="m-snap-hint only-m" aria-hidden="true">Desliza para ver más <?= icon('arrow', 'arr') ?></p>
</section>

<section class="wrap sec-b" id="cortejo">
  <div class="g2 nv-cortejo">
    <div class="ph px ar-45 m-43 arch-soft"><?= img('fotos/novia-madre', 'Novia y su mamá abrazadas en batas de seda blanca y rosa durante la preparación', ['sizes' => '(max-width: 960px) 100vw, 560px', 'class' => 'fill']) ?></div>
    <div class="stack gap-32">
      <h2 class="d d-m">Mamá, damas<br>e invitadas.</h2>
      <p class="body-l mw-480">Una boda casi nunca es un solo maquillaje. Agenda a quien te acompaña el mismo día y mantén un solo estilo en todas las fotos.</p>
      <div class="price-list">
        <div class="price-row"><div><p class="name">Pack 1 · Servicio base</p><p class="detail">Asesoría, maquillaje social y pestañas</p></div><p class="amount"><?= e(money($packs['pack1']['price'])) ?></p></div>
        <div class="price-row"><div><p class="name">Pack 2 · Full experience</p><p class="detail">Skin care, silla y luz de estudio, kit de retoque</p></div><p class="amount"><?= e(money($packs['pack2']['price'])) ?></p></div>
      </div>
    </div>
  </div>
</section>

<section class="bg-arena sec">
  <div class="wrap g2 nv-season">
    <div class="stack gap-24">
      <p class="lbl c-terracota">Temporada de bodas</p>
      <p class="d nv-season-d">Octubre, noviembre y diciembre son los meses más pedidos. Las fechas de 2027 ya se están separando.</p>
    </div>
    <div class="nv-season-cta"><a class="btn" href="/reservar?servicio=pack3">Ver fechas disponibles</a></div>
  </div>
</section>

<section class="wrap sec">
  <div class="g2 nv-faq-grid">
    <div class="stack gap-18">
      <h2 class="d d-l2">Todo claro<br>antes del sí.</h2>
      <p class="body-l mw-460">¿Cuánto suma el cortejo y qué cambia el valor final? Lo explicamos en la guía de <a href="/diario/precio-maquillaje-de-novia-bucaramanga">precio del maquillaje de novia en Bucaramanga</a>.</p>
      <p class="body-l mw-460">¿Es para unos quince años? Mira el <a href="/maquillaje-para-quinceaneras-bucaramanga">maquillaje para quinceañeras</a>.</p>
    </div>
    <div class="faq">
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
  <div class="cta mesh nv-cta">
    <div class="deco" aria-hidden="true"><img src="<?= e(img_src('marca/isotipo-nude', 360)) ?>" alt="" width="360" height="360" loading="lazy"></div>
    <h2 class="d d-xl">Separa tu fecha.</h2>
    <div class="wrap-row cta-actions">
      <a class="btn" href="<?= e(wa_href('novias-cta', 'novias')) ?>" rel="nofollow">Escribir por WhatsApp <?= icon('arrow', 'arr') ?></a>
      <a class="btn ghost" href="/reservar?servicio=pack3">Armar mi solicitud</a>
    </div>
  </div>
</section>
