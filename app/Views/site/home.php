<?php
/**
 * Inicio (maqueta Main.dc.html).
 * @var list<array{q:string,a:string}> $faq
 * @var array<string,array{name:string,price:int}> $packs
 * @var array<string,array{name:string,price:int}> $courses
 */
?>
<section class="wrap hero">
  <div class="stack gap-40">
    <h1 class="hero-h1">
      <span class="lbl in c-terracota">Maquillaje profesional en Bucaramanga</span>
      <span class="d d-hero in d1">Natural.<br>Duradero.<br>Tuyo.</span>
    </h1>
    <p class="lead mw-470 in d2">Maquillaje para novias, quinceañeras, grados y eventos sociales, con acabado natural que se mantiene impecable de la primera foto a la última canción. Estudio en Floridablanca y servicio a domicilio.</p>
    <div class="wrap-row in d3">
      <a class="btn" href="<?= e(wa_href('inicio-hero')) ?>" rel="nofollow">Agendar por WhatsApp <?= icon('arrow', 'arr') ?></a>
      <a class="btn ghost hide-m" href="/precios-maquillaje-bucaramanga">Ver servicios y precios</a>
    </div>
    <nav class="hero-quick only-m in d3" aria-label="Servicios y precios">
      <?php foreach ([
          ['/maquillaje-de-novia-bucaramanga', 'Novias y quinceañeras', 'Prueba previa, reel y extra fijación', $packs['pack3']['price']],
          ['/precios-maquillaje-bucaramanga#packs', 'Social y grados', 'Asesoría, maquillaje y pestañas', $packs['pack1']['price']],
          ['/precios-maquillaje-bucaramanga#domicilio', 'A domicilio', 'El estudio va a tu casa', $packs['pack2']['price']],
          ['/cursos-de-maquillaje-bucaramanga', 'Cursos', 'Automaquillaje y profesional', min($courses['curso-auto']['price'], $courses['curso-pro']['price'])],
      ] as $k => [$href, $name, $detail, $price]): ?>
      <a href="<?= e($href) ?>"><span class="hq-t"><span class="hq-n"><?= e($name) ?></span><span class="hq-d"><?= e($detail) ?></span></span><span class="hq-p"><?= $k === 3 ? 'desde ' : '' ?><?= e(money($price, false)) ?></span><?= icon('chevron', 'hq-i') ?></a>
      <?php endforeach; ?>
    </nav>
    <dl class="hero-facts hide-m in d4">
      <div><dt class="lbl c-topo">Estudio</dt><dd>Floridablanca, Santander</dd></div>
      <div><dt class="lbl c-topo">Servicio</dt><dd>En estudio y a domicilio</dd></div>
      <div><dt class="lbl c-topo">Desde</dt><dd><?= e(money($packs['pack1']['price'])) ?></dd></div>
    </dl>
  </div>
  <div class="hero-media in d1">
    <div class="hero-sun" aria-hidden="true"></div>
    <div class="arch px op-top">
      <?= img('fotos/novia-tiara-retrato', 'Novia con tiara y velo, piel luminosa y labios rosados, sonriendo con su ramo de flores blancas', ['priority' => true, 'sizes' => '(max-width: 960px) 80vw, 520px']) ?>
    </div>
    <div class="seal" aria-hidden="true">
      <svg viewBox="0 0 150 150"><defs><path id="sealc" d="M75,75 m-58,0 a58,58 0 1,1 116,0 a58,58 0 1,1 -116,0"/></defs><text font-family="DM Sans, sans-serif" font-size="9.5" letter-spacing="2" fill="#171312"><textPath href="#sealc" textLength="360" lengthAdjust="spacing">MAKE UP ARTIST · BUCARAMANGA · FLORIDABLANCA · </textPath></text></svg>
      <img src="<?= e(img_src('marca/isotipo-salmon', 120)) ?>" alt="" width="58" height="58">
    </div>
  </div>
</section>

<div class="marq" aria-hidden="true">
  <div class="marq-t">
    <?php foreach ([1, 2] as $_): ?>
      <?php foreach (['Novias', 'Quinceañeras', 'Grados', 'Maquillaje social', 'A domicilio', 'Cursos'] as $w): ?>
      <span class="d"><?= e($w) ?></span><span class="dot"></span>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </div>
</div>

<section class="wrap sec">
  <div class="g2 firma">
    <div class="rv stack gap-20">
      <div class="ph ar-45 firma-ph op-left"><?= img('fotos/catalogo-maria-paula-tablero', 'María Paula Fletcher en su estudio, junto al tablero de clase', ['sizes' => '300px']) ?></div>
      <p class="lbl c-topo">María Paula Fletcher · Creadora de MUBF</p>
    </div>
    <div class="rv stack gap-40">
      <p class="lbl c-terracota">La firma</p>
      <p class="d firma-quote">Creemos en una piel que se ve como piel. Luz donde hace falta, color con intención y una fijación pensada para <span class="c-terracota">llorar, abrazar y bailar</span> sin retoques de emergencia.</p>
      <a class="link-line" href="/estudio-maquillaje-floridablanca">Conoce el estudio <?= icon('arrow', 'arr') ?></a>
    </div>
  </div>
</section>

<section class="wrap sec-b">
  <div class="rv sec-head">
    <div>
      <p class="lbl c-terracota">Servicios</p>
      <h2 class="d d-l">Tres experiencias,<br>un mismo acabado.</h2>
    </div>
    <a class="btn ghost hide-m" href="/precios-maquillaje-bucaramanga">Comparar servicios</a>
  </div>
  <div class="g3 m-snap" aria-label="Servicios">
    <?php
    $cards = [
        ['pack1', 'Pack 01', 'Maquillaje social', 'fotos/social-cumple-pastel', 'Clienta con maquillaje social de ojos dorados y pestañas, sonriendo con su torta de cumpleaños', 'Asesoría, maquillaje social y pestañas. Ideal para grados, cenas, sesiones y eventos.', '/precios-maquillaje-bucaramanga#packs', ''],
        ['pack2', 'Pack 02', 'Full experience a domicilio', 'fotos/proceso-domicilio', 'Maquilladora aplicando rubor a una novia en bata en su casa, con el vestido colgado al fondo', 'Una sesión premium en la comodidad de tu casa: skin care con mascarilla y masaje, silla y luz de estudio, pestañas y kit de retoque.', '/precios-maquillaje-bucaramanga#domicilio', ''],
        ['pack3', 'Pack 03', 'Novias y quinceañeras', 'fotos/novia-ramo-ceremonia', 'Novia con tiara y vestido de encaje sosteniendo su ramo durante la ceremonia', 'Prueba previa con dos propuestas, skin care, maquillaje el día del evento, video reel y extra fijación.', '/maquillaje-de-novia-bucaramanga', 'op-top'],
    ];
    foreach ($cards as $i => [$code, $tag, $title, $photo, $alt, $text, $href, $op]): ?>
    <a class="card rv" href="<?= e($href) ?>" data-delay="<?= e((string) ($i * 0.08)) ?>">
      <div class="ph ar-45 <?= e($op) ?>"><?= img($photo, $alt, ['sizes' => '(max-width: 960px) 80vw, 420px']) ?><span class="tag"><?= e($tag) ?></span></div>
      <div class="between"><h3 class="d d-card"><?= e($title) ?></h3><p class="price"><?= e(money($packs[$code]['price'])) ?></p></div>
      <p><?= e($text) ?></p>
      <hr class="hr">
    </a>
    <?php endforeach; ?>
  </div>
  <p class="m-snap-hint only-m" aria-hidden="true">Desliza para comparar <?= icon('arrow', 'arr') ?></p>
  <a class="btn ghost only-m m-full" href="/precios-maquillaje-bucaramanga">Comparar servicios y precios</a>
</section>

<section class="bg-tinta">
  <div class="wrap g2 novias-band">
    <div class="ph px grow novias-band-ph"><?= img('fotos/novia-jardin-cuello', 'Novia con tiara y vestido de encaje en un jardín, ojos cerrados y piel luminosa', ['sizes' => '(max-width: 960px) 100vw, 640px', 'class' => 'fill']) ?></div>
    <div class="novias-band-txt stack gap-36">
      <p class="lbl rv c-salmon">Novias · Quinceañeras</p>
      <h2 class="d d-l2 rv">El día que más<br>te van a mirar.</h2>
      <p class="rv novias-band-p">Una prueba previa para encontrar tu versión, dos propuestas para elegir con calma y una fijación pensada para doce horas de abrazos, fotos y pista.</p>
      <div class="steps on-dark rv">
        <div><p class="lbl">01</p><p>Prueba previa</p></div>
        <div><p class="lbl">02</p><p>Dos propuestas</p></div>
        <div><p class="lbl">03</p><p>Video reel</p></div>
        <div><p class="lbl">04</p><p>Extra fijación</p></div>
      </div>
      <div class="rv wrap-row novias-band-cta">
        <a class="btn light" href="/maquillaje-de-novia-bucaramanga">Conocer la experiencia novia</a>
      </div>
    </div>
  </div>
</section>

<section class="sec pf" aria-labelledby="pf-title" data-hrail>
  <div class="pf-pin">
    <div class="wrap rv sec-head">
      <div>
        <p class="lbl c-terracota">Portafolio</p>
        <h2 class="d d-l" id="pf-title">Pieles reales,<br>luz real.</h2>
      </div>
      <a class="btn ghost" href="/portafolio">Ver portafolio completo</a>
    </div>
    <?php
    $rail = [
        ['fotos/novia-jardin-velo', 'Novia con velo largo y trenza mirando su ramo en un jardín', 'Novia'],
        ['fotos/social-cumple-bengala', 'Clienta sonriendo con una torta de cumpleaños y una bengala encendida', 'Social'],
        ['fotos/novia-calas-editorial', 'Novia con velo y vestido de seda entre calas blancas', 'Editorial'],
        ['fotos/proceso-labios-perfil', 'Perfil de una novia mientras le delinean los labios', 'Proceso'],
        ['fotos/proceso-rubor-novia', 'Novia con tiara riendo mientras le aplican rubor con brocha', 'Detrás de cámaras'],
        ['fotos/proceso-labios-detalle', 'Detalle de labios con brillo y pincel de labial', 'Detalle'],
    ];
    $total = count($rail) + 1;
    ?>
    <div class="rail-viewport">
      <ul class="rail" id="pf-rail" tabindex="0" aria-label="Trabajos recientes, <?= $total ?> elementos. Usa las flechas para desplazarte." data-rail-track>
        <?php foreach ($rail as $i => [$photo, $alt, $cap]): ?>
        <li class="rail-item" aria-label="<?= $i + 1 ?> de <?= $total ?>">
          <figure><div class="ph ar-34"><?= img($photo, $alt, ['sizes' => '(max-width: 960px) 78vw, 340px']) ?></div><figcaption class="lbl"><span class="rail-n" aria-hidden="true"><?= sprintf('%02d', $i + 1) ?></span><?= e($cap) ?></figcaption></figure>
        </li>
        <?php endforeach; ?>
        <li class="rail-item" aria-label="<?= $total ?> de <?= $total ?>">
          <a class="rail-more" href="/portafolio">
            <span class="lbl c-terracota">Portafolio completo</span>
            <span class="d rail-more-t">Novias, social, editorial y detrás de cámaras.</span>
            <span class="rail-more-cta">Ver todo el portafolio <?= icon('arrow', 'arr') ?></span>
          </a>
        </li>
      </ul>
    </div>
    <div class="wrap rail-ui" data-rail-ui hidden>
      <p class="rail-count"><span data-rail-current>01</span><span class="rail-sep" aria-hidden="true"> / </span><span class="sr-only"> de </span><span><?= sprintf('%02d', $total) ?></span></p>
      <div class="rail-progress" aria-hidden="true"><span data-rail-bar></span></div>
      <div class="rail-btns">
        <button class="rail-btn" type="button" aria-controls="pf-rail" aria-label="Trabajo anterior" data-rail-prev><?= icon('chevron-left') ?></button>
        <button class="rail-btn" type="button" aria-controls="pf-rail" aria-label="Trabajo siguiente" data-rail-next><?= icon('chevron') ?></button>
      </div>
      <p class="sr-only" aria-live="polite" data-rail-live></p>
    </div>
  </div>
</section>

<section class="wrap sec-b">
  <div class="g2 rv cursos-band">
    <div class="cursos-band-txt stack gap-32">
      <p class="lbl c-terracota">Cursos de maquillaje</p>
      <h2 class="d d-m">Aprende la técnica<br>detrás del acabado.</h2>
      <div class="price-list">
        <div class="price-row"><div><p class="name">Automaquillaje</p><p class="detail">5 módulos · para ti</p></div><p class="amount"><?= e(money($courses['curso-auto']['price'])) ?></p></div>
        <div class="price-row"><div><p class="name">Maquillaje profesional</p><p class="detail">7 módulos · para trabajar en el oficio</p></div><p class="amount"><?= e(money($courses['curso-pro']['price'])) ?></p></div>
      </div>
      <a class="btn self-start" href="/cursos-de-maquillaje-bucaramanga">Ver contenido de los cursos</a>
    </div>
    <div class="ph px cursos-band-ph"><?= img('fotos/catalogo-alumna-curso', 'Alumna tomando apuntes durante un curso de maquillaje en el estudio', ['sizes' => '(max-width: 960px) 100vw, 620px', 'class' => 'fill']) ?></div>
  </div>
</section>

<section class="wrap sec-b">
  <div class="g2">
    <div class="ph px rv ar-45 m-43 arch-soft"><?= img('fotos/catalogo-brindis-estudio', 'María Paula Fletcher brindando con una clienta en el estudio de Floridablanca', ['sizes' => '(max-width: 960px) 100vw, 620px', 'class' => 'fill']) ?></div>
    <div class="stack gap-32">
      <p class="lbl rv c-terracota">El estudio</p>
      <h2 class="d d-l rv">Floridablanca,<br>a tu medida.</h2>
      <p class="rv body-l mw-460">Un espacio con luz de estudio para que el maquillaje se vea igual en el espejo, en la foto y en persona. Si prefieres no moverte, llevamos el estudio a tu casa en Bucaramanga, Floridablanca, Piedecuesta o Girón.</p>
      <address class="rv stack gap-4 fs-16 addr">
        <span>Carrera 29 #158-08</span>
        <span class="c-topo">Floridablanca, Santander</span>
      </address>
      <div class="rv wrap-row">
        <a class="btn" href="/estudio-maquillaje-floridablanca#ubicacion">Cómo llegar</a>
        <a class="btn ghost" href="<?= e(wa_href('inicio-domicilio', 'domicilio')) ?>" rel="nofollow">Pedir servicio a domicilio</a>
      </div>
    </div>
  </div>
</section>

<section class="bg-arena sec">
  <div class="wrap">
    <div class="rv stack gap-18 reviews-head">
      <p class="lbl c-terracota">Reseñas en Google</p>
      <h2 class="d d-l">Lo que dicen ellas.</h2>
    </div>
    <div class="g3 m-snap">
      <?php foreach ([['[Reseña real de una novia, copiada de Google Business Profile]', 'Novia'], ['[Reseña real de una quinceañera o su mamá]', 'Quinceañera'], ['[Reseña real de una clienta de maquillaje social o grados]', 'Grado']] as $i => [$quote, $type]): ?>
      <figure class="review rv" data-delay="<?= e((string) ($i * 0.08)) ?>">
        <?= App\View::partial('partials/stars', []) ?>
        <blockquote><span class="pending"><?= e($quote) ?></span></blockquote>
        <figcaption class="lbl"><span class="pending">[Nombre]</span> · <?= e($type) ?></figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="wrap sec">
  <div class="g2 faq-grid">
    <div class="rv stack gap-18">
      <p class="lbl c-terracota">Preguntas frecuentes</p>
      <h2 class="d d-l2">Antes de<br>reservar.</h2>
    </div>
    <div class="rv"><?= App\View::partial('partials/faq', ['items' => $faq]) ?></div>
  </div>
</section>

<?= App\View::partial('partials/cta', [
    'label' => 'Agenda 2026 · 2027',
    'title' => 'Reserva tu fecha.',
    'text' => 'Cuéntanos el día, la hora y el tipo de evento. Te respondemos con disponibilidad y la propuesta para ti.',
    'button' => 'Escribir por WhatsApp',
    'href' => wa_href('inicio-cta'),
    'wa' => true,
    'secondaryText' => 'Reservar en línea',
    'secondaryHref' => '/reservar',
]) ?>
