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
      <span class="lbl c-terracota">Maquillaje profesional en Bucaramanga</span>
      <span class="d d-hero hero-t">Que te veas tú hasta la última canción.</span>
    </h1>
    <p class="lead mw-470">Maquillaje para novias, quinceañeras, grados y eventos sociales, con acabado natural que se mantiene impecable de la primera foto a la última del álbum. Estudio en Floridablanca y servicio a domicilio.</p>
    <div class="wrap-row">
      <a class="btn" href="<?= e(wa_href('inicio-hero')) ?>" rel="nofollow">Agendar por WhatsApp <?= icon('arrow', 'arr') ?></a>
      <a class="btn ghost hide-m" href="/precios-maquillaje-bucaramanga">Ver servicios y precios</a>
    </div>
    <nav class="hero-quick only-m" aria-label="Servicios y precios">
      <?php foreach ([
          ['/maquillaje-de-novia-bucaramanga', 'Novias y quinceañeras', 'Prueba previa, reel y extra fijación', $packs['pack3']['price']],
          ['/precios-maquillaje-bucaramanga#packs', 'Social y grados', 'Asesoría, maquillaje y pestañas', $packs['pack1']['price']],
          ['/precios-maquillaje-bucaramanga#domicilio', 'A domicilio', 'El estudio va a tu casa', $packs['pack2']['price']],
          ['/cursos-de-maquillaje-bucaramanga', 'Cursos', 'Automaquillaje y profesional', min($courses['curso-auto']['price'], $courses['curso-pro']['price'])],
      ] as $k => [$href, $name, $detail, $price]): ?>
      <a href="<?= e($href) ?>"><span class="hq-t"><span class="hq-n"><?= e($name) ?></span><span class="hq-d"><?= e($detail) ?></span></span><span class="hq-p"><?= $k === 3 ? 'desde ' : '' ?><?= e(money($price, false)) ?></span><?= icon('chevron', 'hq-i') ?></a>
      <?php endforeach; ?>
    </nav>
    <dl class="hero-facts hide-m">
      <div><dt class="lbl c-topo">Estudio</dt><dd>Floridablanca, Santander</dd></div>
      <div><dt class="lbl c-topo">Servicio</dt><dd>En estudio y a domicilio</dd></div>
      <div><dt class="lbl c-topo">Desde</dt><dd><?= e(money($packs['pack1']['price'])) ?></dd></div>
    </dl>
  </div>
  <div class="hero-media">
    <div class="hero-sun" aria-hidden="true"></div>
    <div class="arch px op-top">
      <?= img('fotos/novia-tiara-retrato', 'Novia con tiara y velo, piel luminosa y labios rosados, sonriendo con su ramo de flores blancas', ['priority' => true, 'sizes' => '(max-width: 960px) 80vw, 520px']) ?>
    </div>
    <?= App\View::partial('partials/seal', []) ?>
  </div>
</section>

<section class="wrap sec">
  <div class="g2 firma">
    <div class="stack gap-20">
      <div class="ph ar-45 firma-ph op-left"><?= img('fotos/catalogo-maria-paula-tablero', 'María Paula Fletcher en su estudio, junto al tablero de clase', ['sizes' => '300px']) ?></div>
      <p class="lbl c-topo">María Paula Fletcher · Creadora de MUBF</p>
    </div>
    <div class="stack gap-40">
      <p class="d firma-quote" data-ink>Creemos en una piel que se ve como piel<?php foreach (['fotos/novia-rostro', 'fotos/social-cumple-beso', 'fotos/novia-bata-sonrisa'] as $q): ?><span class="qface" aria-hidden="true"><?= img($q, '', ['sizes' => '64px', 'width' => 480]) ?></span><?php endforeach; ?>. Luz donde hace falta, color con intención y una fijación pensada para <span class="c-terracota">llorar, abrazar y bailar</span> sin retoques de emergencia.</p>
      <a class="link-line" href="/estudio-maquillaje-floridablanca">Conoce el estudio <?= icon('arrow', 'arr') ?></a>
    </div>
  </div>
</section>

<section class="wrap sec-b">
  <div class="sec-head">
    <div>
      <h2 class="d d-l">Tres experiencias,<br>un mismo acabado.</h2>
    </div>
    <a class="btn ghost hide-m" href="/precios-maquillaje-bucaramanga">Comparar servicios</a>
  </div>
  <ul class="flist hide-m" data-follow role="list">
    <?php foreach ([
        ['01', 'Maquillaje social', 'Asesoría, maquillaje y pestañas para grados, cenas, sesiones y eventos.', $packs['pack1']['price'], '/precios-maquillaje-bucaramanga#pk-pack1', 'fotos/estudio-vestido-azul-sonrisa'],
        ['02', 'Full experience a domicilio', 'Skin care, silla y luz de estudio en tu casa, pestañas y kit de retoque.', $packs['pack2']['price'], '/precios-maquillaje-bucaramanga#pk-pack2', 'fotos/proceso-domicilio'],
        ['03', 'Novias y quinceañeras', 'Prueba previa con dos propuestas, video reel y extra fijación.', $packs['pack3']['price'], '/maquillaje-de-novia-bucaramanga', 'fotos/novia-ramo-ceremonia'],
    ] as [$n, $title, $text, $price, $href, $photo]): ?>
    <li class="frow" data-follow-row>
      <a href="<?= e($href) ?>">
        <span class="frow-n"><?= e($n) ?></span>
        <span class="frow-t"><?= e($title) ?></span>
        <span class="frow-d"><?= e($text) ?></span>
        <span class="frow-p"><?= e(money($price)) ?></span>
        <span class="frow-go" aria-hidden="true"><?= icon('arrow', 'arr') ?></span>
      </a>
      <span class="frow-ph" data-follow-ph aria-hidden="true"><?= img($photo, '', ['sizes' => '230px', 'width' => 480]) ?></span>
    </li>
    <?php endforeach; ?>
  </ul>
  <div class="g3 m-snap only-m" aria-label="Servicios">
    <?php
    $cards = [
        ['pack1', 'Pack 01', 'Maquillaje social', 'fotos/estudio-vestido-azul-sonrisa', 'Clienta con vestido azul bordado, ojos ahumados en tonos cálidos y pestañas, sonriendo en el estudio', 'Asesoría, maquillaje social y pestañas. Ideal para grados, cenas, sesiones y eventos.', '/precios-maquillaje-bucaramanga#packs', ''],
        ['pack2', 'Pack 02', 'Full experience a domicilio', 'fotos/proceso-domicilio', 'Maquilladora aplicando rubor a una novia en bata en su casa, con el vestido colgado al fondo', 'Una sesión premium en la comodidad de tu casa: skin care con mascarilla y masaje, silla y luz de estudio, pestañas y kit de retoque.', '/precios-maquillaje-bucaramanga#domicilio', ''],
        ['pack3', 'Pack 03', 'Novias y quinceañeras', 'fotos/novia-ramo-ceremonia', 'Novia con tiara y vestido de encaje sosteniendo su ramo durante la ceremonia', 'Prueba previa con dos propuestas, skin care, maquillaje el día del evento, video reel y extra fijación.', '/maquillaje-de-novia-bucaramanga', 'op-top'],
    ];
    foreach ($cards as $i => [$code, $tag, $title, $photo, $alt, $text, $href, $op]): ?>
    <a class="card" href="<?= e($href) ?>">
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
      <h2 class="d d-l2">El día que más<br>te van a mirar.</h2>
      <p class="novias-band-p">Una prueba previa para encontrar tu versión, dos propuestas para elegir con calma y una fijación pensada para doce horas de abrazos, fotos y pista.</p>
      <div class="steps on-dark">
        <div><p class="lbl">01</p><p>Prueba previa</p></div>
        <div><p class="lbl">02</p><p>Dos propuestas</p></div>
        <div><p class="lbl">03</p><p>Video reel</p></div>
        <div><p class="lbl">04</p><p>Extra fijación</p></div>
      </div>
      <div class="wrap-row novias-band-cta">
        <a class="btn light" href="/maquillaje-de-novia-bucaramanga">Conocer la experiencia novia</a>
      </div>
    </div>
  </div>
</section>

<section class="sec pf" aria-labelledby="pf-title" data-hrail>
  <div class="pf-pin">
    <div class="wrap sec-head">
      <div>
        <h2 class="d d-l" id="pf-title">Pieles reales,<br>luz real.</h2>
      </div>
      <a class="btn ghost" href="/portafolio">Ver portafolio completo</a>
    </div>
    <?php
    $rail = [
        ['fotos/novia-jardin-velo', 'Novia con velo largo y trenza mirando su ramo en un jardín', 'Novia'],
        ['fotos/estudio-ondas-vino', 'Clienta con ondas suaves, vestido vino y maquillaje de piel satinada con labio coral', 'Social'],
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
  <div class="g2 cursos-band">
    <div class="cursos-band-txt stack gap-32">
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
    <div class="ph px ar-45 m-43 arch-soft"><?= img('fotos/catalogo-brindis-estudio', 'María Paula Fletcher brindando con una clienta en el estudio de Floridablanca', ['sizes' => '(max-width: 960px) 100vw, 620px', 'class' => 'fill']) ?></div>
    <div class="stack gap-32">
      <h2 class="d d-l">Floridablanca,<br>a tu medida.</h2>
      <p class="body-l mw-460">Un espacio con luz de estudio para que el maquillaje se vea igual en el espejo, en la foto y en persona. Si prefieres no moverte, llevamos el estudio a tu casa en Bucaramanga, Floridablanca, Piedecuesta o Girón.</p>
      <address class="stack gap-4 fs-16 addr">
        <span><?= e(config('app.business.address')) ?></span>
        <span class="c-topo">Floridablanca, Santander</span>
      </address>
      <div class="wrap-row">
        <a class="btn" href="/estudio-maquillaje-floridablanca#ubicacion">Cómo llegar</a>
        <a class="btn ghost" href="<?= e(wa_href('inicio-domicilio', 'domicilio')) ?>" rel="nofollow">Pedir servicio a domicilio</a>
      </div>
    </div>
  </div>
</section>

<section class="wrap sec">
  <div class="g2 faq-grid">
    <div class="stack gap-18">
      <h2 class="d d-l2">Antes de<br>reservar.</h2>
    </div>
    <div><?= App\View::partial('partials/faq', ['items' => $faq]) ?></div>
  </div>
</section>

<section class="wrap sec-b">
  <div class="orbit" data-orbit>
    <div class="orbit-ring" aria-hidden="true">
      <div class="orbit-spin">
        <?php foreach (['novia-tiara-ramo', 'social-cumple-bengala', 'novia-jardin-velo', 'proceso-labios-detalle', 'novia-cortina-retrato', 'social-cumple-globos', 'novia-calas-editorial', 'proceso-rubor-novia'] as $i => $o): ?>
        <div class="orbit-t o<?= $i + 1 ?>"><div class="ph"><?= img('fotos/' . $o, '', ['sizes' => '112px', 'width' => 480]) ?></div></div>
        <?php endforeach; ?>
      </div>
      <div class="orbit-core"><?= App\View::partial('partials/seal', []) ?></div>
    </div>
    <div class="orbit-c rv">
      <?= App\View::partial('partials/seal', []) ?>
      <p class="lbl c-terracota">Agenda 2026 · 2027</p>
      <h2 class="d d-xl">Reserva tu fecha.</h2>
      <p class="txt">Elige el servicio y la fecha: se abre WhatsApp con tu solicitud escrita y te respondemos con la disponibilidad.</p>
      <div class="wrap-row cta-actions">
        <a class="btn" href="/reservar">Armar mi solicitud <?= icon('arrow', 'arr') ?></a>
        <a class="btn ghost" href="<?= e(wa_href('inicio-cta')) ?>" rel="nofollow"><?= icon('whatsapp') ?> WhatsApp</a>
      </div>
    </div>
  </div>
</section>
