<?php
/**
 * Artículo del diario (maqueta Articulo.dc.html).
 * @var array<string,mixed> $post
 * @var string $body   HTML saneado con precios vigentes
 * @var list<array{q:string,a:string}> $faq
 * @var list<array{id:string,text:string}> $toc
 * @var list<array<string,mixed>> $related
 * @var DateTimeImmutable|null $published
 * @var string $shareUrl
 * @var list<array{0:string,1:?string}> $crumbs
 */
$shareText = $post['title'] . ' ' . $shareUrl;
$serviceLinks = [
    '/maquillaje-de-novia-bucaramanga' => ['Ver experiencia novia', 'Novias 2027', '¿Ya tienes fecha? Revisemos disponibilidad.', 'novias'],
    '/precios-maquillaje-bucaramanga' => ['Ver servicios y precios', 'Agenda 2026 · 2027', '¿Tienes un evento? Revisemos disponibilidad.', null],
    '/cursos-de-maquillaje-bucaramanga' => ['Ver los cursos', 'Cursos', '¿Quieres aprender la técnica? Te contamos fechas.', 'cursos'],
];
// Texto alternativo descriptivo de cada foto de portada (el título no describe la imagen)
$coverAlts = [
    'fotos/catalogo-ojo-detalle' => 'Detalle de ojo maquillado para novia con sombras cálidas y delineado',
    'fotos/ig-novia-reel' => 'Novia en bata de seda durante la prueba de maquillaje',
    'fotos/ig-clienta-bata-seda' => 'Clienta en bata de seda con maquillaje natural para su boda',
    'fotos/ig-maquillaje-social' => 'Maquillaje social con piel luminosa y ojo ahumado suave',
    'fotos/catalogo-modelo-hero' => 'Modelo con piel luminosa entre flores',
    'fotos/catalogo-paleta' => 'Aplicación de corrector con pincel desde la paleta',
    'fotos/catalogo-alumna-curso' => 'Alumna tomando apuntes en un curso de maquillaje',
    'fotos/catalogo-maria-paula-tablero' => 'María Paula Fletcher enseñando frente al tablero',
    'fotos/catalogo-brindis-estudio' => 'Clienta y maquilladora brindando en el estudio',
];
$coverAlt = $coverAlts[(string) $post['cover']] ?? (string) $post['title'];
$linksTo = (string) ($post['links_to'] ?? '');
$linkBase = strtok($linksTo, '#') ?: '';
[$serviceLabel, $ctaLabel, $ctaTitle, $waTopic] = $serviceLinks[$linkBase] ?? ['Ver servicios y precios', 'Agenda 2026 · 2027', '¿Tienes una fecha? Revisemos disponibilidad.', null];
?>
<div class="progress" data-progress aria-hidden="true"></div>
<article>
  <header class="wrap art-head">
    <?= App\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
    <p class="lbl c-terracota in art-kicker"><?= e($post['kicker'] ?: $post['category']) ?></p>
    <h1 class="d in d1 art-title"><?= e($post['title']) ?></h1>
    <div class="art-byline in d2">
      <div class="row gap-14">
        <div class="ph avatar"><?= img('fotos/catalogo-maria-paula-tablero', '', ['sizes' => '52px', 'width' => 480]) ?></div>
        <div>
          <p class="fs-15">María Paula Fletcher</p>
          <p class="art-date"><?php if ($published): ?><time datetime="<?= e($published->format('Y-m-d')) ?>"><?= e(fecha_larga($published, false)) ?></time> · <?php endif; ?><?= e((string) $post['reading_minutes']) ?> min de lectura</p>
        </div>
      </div>
      <div class="share" role="group" aria-label="Compartir">
        <a href="https://wa.me/?text=<?= e(rawurlencode($shareText)) ?>" target="_blank" rel="noopener nofollow" aria-label="Compartir por WhatsApp"><?= icon('whatsapp', 'ic sm') ?></a>
        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= e(rawurlencode($shareUrl)) ?>" target="_blank" rel="noopener nofollow" aria-label="Compartir en Facebook"><?= icon('facebook', 'ic sm') ?></a>
        <a href="<?= e($shareUrl) ?>" data-copy="<?= e($shareUrl) ?>" aria-label="Copiar enlace"><?= icon('link', 'ic sm') ?></a>
        <span class="copied" role="status" aria-live="polite" data-copied></span>
      </div>
    </div>
  </header>

  <?php if (!empty($post['cover'])): ?>
  <div class="wrap in d3 art-cover-wrap">
    <div class="ph art-cover"><?= img((string) $post['cover'], $coverAlt, ['priority' => true, 'sizes' => '(max-width: 1320px) 100vw, 1240px']) ?></div>
  </div>
  <?php endif; ?>

  <div class="wrap layout sec-b">
    <?php if (count($toc) > 1): ?>
    <nav class="toc" aria-label="En este artículo">
      <p class="lbl c-topo toc-title">En este artículo</p>
      <?php foreach ($toc as $h): ?>
      <a href="#<?= e($h['id']) ?>"><?= e($h['text']) ?></a>
      <?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <div class="prose">
      <?= $body ?>

      <div class="rv art-cta">
        <p class="lbl c-salmon"><?= e($ctaLabel) ?></p>
        <p class="d art-cta-title"><?= e($ctaTitle) ?></p>
        <div class="wrap-row">
          <a class="btn salmon" href="<?= e(wa_href('diario-articulo', $waTopic)) ?>" rel="nofollow">Consultar por WhatsApp</a>
          <?php if ($linksTo !== ''): ?>
          <a class="btn ghost-light" href="<?= e($linksTo) ?>"><?= e($serviceLabel) ?></a>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($faq !== []): ?>
      <h2 id="preguntas">Preguntas frecuentes</h2>
      <?= App\View::partial('partials/faq', ['items' => $faq, 'class' => 'sm']) ?>
      <?php endif; ?>

      <div class="rv author">
        <div class="ph avatar lg"><?= img('fotos/catalogo-maria-paula-tablero', 'María Paula Fletcher', ['sizes' => '88px', 'width' => 480]) ?></div>
        <div>
          <p class="lbl c-topo">Escrito por</p>
          <p class="author-name">María Paula Fletcher</p>
          <p class="author-bio">Creadora de Make Up by Fletcher. Maquillaje profesional para novias y quinceañeras en Bucaramanga y Floridablanca.</p>
        </div>
      </div>
    </div>
  </div>
</article>

<section class="bg-arena sec">
  <div class="wrap">
    <div class="all-head">
      <h2 class="d more-title">Sigue leyendo</h2>
      <a class="btn ghost" href="/diario">Ver todo el diario</a>
    </div>
    <div class="g3">
      <?php if ($related !== []): ?>
        <?php foreach ($related as $i => $r): ?>
        <a class="post rv" href="/diario/<?= e($r['slug']) ?>" data-delay="<?= e((string) ($i * 0.08)) ?>">
          <div class="ph ar-43"><?= img((string) $r['cover'], (string) $r['title'], ['sizes' => '(max-width: 960px) 100vw, 420px']) ?></div>
          <p class="lbl c-terracota"><?= e($r['category']) ?></p>
          <h3 class="d more-post"><?= e($r['title']) ?></h3>
        </a>
        <?php endforeach; ?>
      <?php else: ?>
        <?php foreach ([
            ['/maquillaje-de-novia-bucaramanga', 'fotos/ig-novia-reel', 'Novia en bata de seda durante la prueba de maquillaje', 'Novias', 'La experiencia novia, paso a paso'],
            ['/precios-maquillaje-bucaramanga', 'fotos/ig-maquillaje-social', 'Maquillaje social con ojo ahumado suave', 'Servicios', 'Servicios y precios de maquillaje 2026'],
            ['/portafolio', 'fotos/catalogo-paleta', 'Aplicación de corrector con pincel desde la paleta', 'Portafolio', 'Pieles reales, luz real: el portafolio'],
        ] as $i => [$href, $photo, $alt, $cat, $label]): ?>
        <a class="post rv" href="<?= e($href) ?>" data-delay="<?= e((string) ($i * 0.08)) ?>">
          <div class="ph ar-43"><?= img($photo, $alt, ['sizes' => '(max-width: 960px) 100vw, 420px']) ?></div>
          <p class="lbl c-terracota"><?= e($cat) ?></p>
          <h3 class="d more-post"><?= e($label) ?></h3>
        </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>
