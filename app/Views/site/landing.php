<?php
/**
 * Página de servicio por intención de búsqueda (quinceañeras, grados, domicilio). Misma familia visual
 * que Novias (pages/novias.css) con ajustes en pages/landing.css. Los datos llegan de LandingController.
 * @var array<string,array{name:string,short:string,price:int,includes:list<string>}> $packs
 * @var string $pack  código del pack principal
 * @var list<array{q:string,a:string}> $faq
 * @var list<array{0:string,1:?string}> $crumbs
 */
$main = $packs[$pack];
$wa = static fn(string $where): string => wa_href($origin . '-' . $where, $topic);
?>
<section class="wrap nv-hero">
  <?= App\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
  <div class="g2 nv-hero-grid">
    <h1 class="nv-h1">
      <span class="lbl c-terracota"><?= e($h1) ?></span>
      <span class="d nv-hero-d lp-hero-d"><?= $display ?></span>
    </h1>
    <div class="stack gap-28 nv-hero-side">
      <p class="lead"><?= e($lead) ?></p>
      <div class="nv-prices">
        <p class="nv-price"><span class="d"><?= e(money($main['price'], false)) ?></span><span class="c-topo"><?= e($priceNote) ?></span></p>
        <a class="nv-price-alt" href="<?= e($priceAlt[0]) ?>"><span><?= e($priceAlt[1]) ?></span><?= icon('arrow', 'arr') ?></a>
      </div>
      <div class="wrap-row">
        <a class="btn" href="<?= e($wa('hero')) ?>" rel="nofollow">Consultar mi fecha</a>
        <a class="btn ghost" href="#incluye">Qué incluye</a>
      </div>
    </div>
  </div>
</section>

<section class="nv-cover-wrap">
  <div class="ph px nv-cover lp-cover">
    <?= img($cover[0], $cover[1], ['priority' => true, 'sizes' => '100vw', 'class' => 'fill']) ?>
  </div>
</section>

<section id="incluye" class="bg-tinta sec">
  <div class="wrap">
    <div class="g2 nv-inc-head">
      <h2 class="d d-l"><?= $incTitle ?></h2>
      <p class="nv-on-dark mw-480"><?= e($incText) ?></p>
    </div>
    <div class="g4 nv-inc-grid">
      <?php foreach ($inc as [$n, $title, $text]): ?>
      <div class="inc">
        <p class="d inc-n" aria-hidden="true"><?= e($n) ?></p>
        <h3><?= e($title) ?></h3>
        <p><?= e($text) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <details class="nv-inc-more">
      <summary><span>Ver todo lo que incluye el <?= e(explode(' · ', $main['name'])[0]) ?></span><?= icon('plus', 'pl') ?></summary>
      <ul class="lp-inc-list">
        <?php if ($pack === 'pack3'): foreach ($packs['pack2']['includes'] as $item): ?><li><?= e($item) ?></li><?php endforeach; endif; ?>
        <?php foreach ($main['includes'] as $item): if ($item === 'Todo lo del Pack 2') { continue; } ?><li><?= e($item) ?></li><?php endforeach; ?>
      </ul>
    </details>
    <div class="nv-inc-cta">
      <a class="btn light" href="<?= e($reserveHref) ?>">Reservar mi fecha</a>
      <a class="btn ghost-light" href="<?= e($wa('incluye')) ?>" rel="nofollow">Consultar por WhatsApp</a>
    </div>
  </div>
</section>

<section class="wrap sec">
  <div class="g2 lp-guide">
    <div class="ph px ar-45 m-43 arch-soft op-top"><?= img($guidePhoto[0], $guidePhoto[1], ['sizes' => '(max-width: 960px) 100vw, 560px', 'class' => 'fill']) ?></div>
    <div class="stack gap-32">
      <h2 class="d d-l"><?= $guideTitle ?></h2>
      <p class="body-l mw-480"><?= e($guideText) ?></p>
      <dl class="lp-points">
        <?php foreach ($guide as [$title, $text]): ?>
        <div><dt><?= e($title) ?></dt><dd><?= e($text) ?></dd></div>
        <?php endforeach; ?>
      </dl>
    </div>
  </div>
</section>

<?php if (!empty($zones)): ?>
<section class="bg-arena sec" aria-labelledby="lp-zonas-t">
  <div class="wrap">
    <div class="stack gap-18 lp-zones-head">
      <h2 class="d d-l" id="lp-zonas-t">Dónde te<br>maquillamos.</h2>
      <p class="body-l mw-480">El Pack 2 tiene el mismo precio en las cuatro ciudades del área metropolitana.</p>
    </div>
    <div class="g4 lp-zones">
      <?php foreach ($zones as [$id, $name, $text]): ?>
      <article class="lp-zone" id="<?= e($id) ?>">
        <h3 class="d lp-zone-t">A domicilio en <?= e($name) ?></h3>
        <p><?= e($text) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="wrap sec" aria-labelledby="lp-steps-t">
  <div class="stack gap-18 nv-steps-head">
    <h2 class="d d-l" id="lp-steps-t">Del primer mensaje<br>a la última foto.</h2>
  </div>
  <ol class="nv-steps">
    <?php foreach ($steps as [$n, $title, $text]): ?>
    <li class="step">
      <p class="lbl c-topo step-n"><span class="sr-only">Paso </span><?= e($n) ?></p>
      <h3 class="d step-t"><?= e($title) ?></h3>
      <p class="step-p"><?= e($text) ?></p>
    </li>
    <?php endforeach; ?>
  </ol>
</section>

<section class="wrap sec-b" aria-labelledby="lp-gal-t">
  <div class="sec-head">
    <div><h2 class="d d-l" id="lp-gal-t"><?= e($galleryTitle) ?></h2></div>
    <a class="btn ghost hide-m" href="/portafolio">Ver portafolio</a>
  </div>
  <ul class="g3 m-snap nv-gal" role="list">
    <?php foreach ($gallery as [$photo, $alt]): ?>
    <li><div class="ph ar-34"><?= img($photo, $alt, ['sizes' => '(max-width: 960px) 80vw, 400px']) ?></div></li>
    <?php endforeach; ?>
  </ul>
  <p class="m-snap-hint only-m" aria-hidden="true">Desliza para ver más <?= icon('arrow', 'arr') ?></p>
</section>

<section class="wrap sec-b" id="<?= e($family['id']) ?>">
  <div class="g2 nv-cortejo">
    <div class="ph px ar-45 m-43 arch-soft"><?= img($family['photo'][0], $family['photo'][1], ['sizes' => '(max-width: 960px) 100vw, 560px', 'class' => 'fill']) ?></div>
    <div class="stack gap-32">
      <h2 class="d d-m"><?= $family['title'] ?></h2>
      <p class="body-l mw-480"><?= e($family['text']) ?></p>
      <div class="price-list">
        <?php foreach (['pack1' => 'Asesoría, maquillaje social y pestañas · en el estudio', 'pack2' => 'Skin care, silla y luz de estudio, kit de retoque · a domicilio', 'pack3' => 'Prueba previa, dos propuestas, video reel y extra fijación'] as $code => $detail): ?>
        <div class="price-row"><div><p class="name"><?= e($packs[$code]['name']) ?></p><p class="detail"><?= e($detail) ?></p></div><p class="amount"><?= e(money($packs[$code]['price'])) ?></p></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="bg-arena sec">
  <div class="wrap g2 nv-season">
    <div class="stack gap-24">
      <p class="lbl c-terracota"><?= e($band[0]) ?></p>
      <p class="d nv-season-d"><?= e($band[1]) ?></p>
    </div>
    <div class="nv-season-cta"><a class="btn" href="<?= e($reserveHref) ?>">Ver fechas disponibles</a></div>
  </div>
</section>

<section class="wrap sec">
  <div class="g2 nv-faq-grid">
    <div class="stack gap-24">
      <h2 class="d d-l2"><?= $faqTitle ?></h2>
      <nav class="lp-related" aria-label="Relacionado">
        <p class="lbl c-topo">También te puede servir</p>
        <?php foreach ($related as [$href, $label]): ?>
        <a class="link-line" href="<?= e($href) ?>"><?= e($label) ?> <?= icon('arrow', 'arr') ?></a>
        <?php endforeach; ?>
      </nav>
    </div>
    <div><?= App\View::partial('partials/faq', ['items' => $faq]) ?></div>
  </div>
</section>

<section class="wrap sec-b">
  <div class="cta mesh nv-cta">
    <div class="deco" aria-hidden="true"><img src="<?= e(img_src('marca/isotipo-nude', 360)) ?>" alt="" width="360" height="360" loading="lazy"></div>
    <h2 class="d d-xl"><?= e($ctaTitle) ?></h2>
    <div class="wrap-row cta-actions">
      <a class="btn" href="<?= e($wa('cta')) ?>" rel="nofollow">Escribir por WhatsApp <?= icon('arrow', 'arr') ?></a>
      <a class="btn ghost" href="<?= e($reserveHref) ?>">Armar mi solicitud</a>
    </div>
  </div>
</section>
