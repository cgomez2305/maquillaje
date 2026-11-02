<?php
/**
 * Tienda (maqueta Tienda.dc.html).
 * @var list<array<string,mixed>> $gifts  bonos activos
 * @var list<array<string,mixed>> $kits   kits (incluye los de precio pendiente)
 * @var list<array{0:string,1:?string}> $crumbs
 */
$giftLabels = ['pack1' => 'Bono · Pack 1', 'pack2' => 'Bono · Pack 2', 'curso-auto' => 'Bono · Curso'];
$giftTitles = ['pack1' => 'Maquillaje social', 'pack2' => 'Full experience', 'curso-auto' => 'Automaquillaje'];
$recommended = [
    ['Piel', '[Primer o hidratante]', '[Para qué piel]'],
    ['Piel', '[Base de larga duración]', '[Para qué piel]'],
    ['Ojos', '[Paleta de sombras]', '[Tonos]'],
    ['Fijación', '[Spray fijador]', '[Duración]'],
];
?>
<section class="wrap shop-hero">
  <?= App\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
  <div class="shop-hero-grid">
    <h1 class="hero-title">
      <span class="lbl in c-terracota">Tienda de maquillaje · Bucaramanga</span>
      <span class="d shop-h1 in d1">Lo que uso<br>en tu piel.</span>
    </h1>
    <p class="lead shop-hero-p in d2">Bonos de regalo, kits y los productos que María Paula usa cada semana en el estudio. Pides por WhatsApp y recoges en Floridablanca o te lo enviamos.</p>
  </div>
</section>

<section class="wrap sec-b">
  <nav class="g4 cats" aria-label="Categorías de la tienda">
    <?php
    $cats = [
        ['#bonos', 'Bonos de regalo', 'fotos/catalogo-brindis-estudio', 'Clienta y maquilladora brindando en el estudio'],
        ['#productos', 'Piel', 'fotos/catalogo-paleta', 'Paleta de correctores y pincel'],
        ['#productos', 'Ojos', 'fotos/catalogo-ojo-detalle', 'Detalle de ojo maquillado'],
        ['#kits', 'Kits', 'fotos/catalogo-alumna-curso', 'Alumna con pinceles y apuntes de clase'],
    ];
    foreach ($cats as $i => [$href, $label, $photo, $alt]): ?>
    <a class="cat rv" href="<?= e($href) ?>" data-delay="<?= e((string) ($i * 0.06)) ?>">
      <?= img($photo, $alt, ['sizes' => '(max-width: 960px) 50vw, 310px', 'eager' => $i < 2, 'priority' => $i === 0]) ?>
      <span><?= e($label) ?> <?= icon('arrow', 'arr') ?></span>
    </a>
    <?php endforeach; ?>
  </nav>
</section>

<section id="bonos" class="bg-arena sec">
  <div class="wrap">
    <div class="rv sec-head">
      <div>
        <p class="lbl c-terracota">Bonos de regalo</p>
        <h2 class="d d-l">Regala una<br>experiencia.</h2>
      </div>
      <p class="shop-note">Para una despedida de soltera, un grado o un cumpleaños. El bono se entrega en tarjeta física o digital con el nombre de quien lo recibe.</p>
    </div>
    <?php if ($gifts === []): ?>
    <p class="notice">Los bonos estarán disponibles muy pronto. Mientras tanto, <a href="<?= e(wa_href('tienda-bonos', 'tienda')) ?>" rel="nofollow">pídelos por WhatsApp</a>.</p>
    <?php else: ?>
    <div class="g3 gifts">
      <?php foreach ($gifts as $i => $p):
          $dark = $i === 1;
          $code = (string) $p['service_code'];
          ?>
      <article class="gift rv<?= $dark ? ' dark' : '' ?>" data-delay="<?= e((string) ($i * 0.06)) ?>">
        <img class="gift-deco" src="<?= e($dark ? img_src('marca/isotipo-nude', 360) : img_src('marca/isotipo-salmon', 240)) ?>" alt="" aria-hidden="true" width="180" height="180" loading="lazy">
        <p class="lbl"><?= e($giftLabels[$code] ?? 'Bono de regalo') ?></p>
        <h3 class="d gift-title"><?= e($giftTitles[$code] ?? preg_replace('/^Bono · /u', '', $p['name'])) ?></h3>
        <p class="gift-text"><?= e($p['summary']) ?></p>
        <div class="gift-foot">
          <span class="d gift-price"><span class="sr-only">Precio: </span><?= e(money($p['price'], false)) ?><span class="sr-only"> pesos colombianos</span></span>
          <a class="add<?= $dark ? ' salmon' : '' ?>" href="/tienda/<?= e($p['slug']) ?>" aria-label="Regalar el <?= e(mb_strtolower($p['name'])) ?>">Regalar</a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<section id="kits" class="wrap sec">
  <div class="g2">
    <div class="ph px rv ar-45 arch-soft"><?= img('fotos/catalogo-asesoria-estudio', 'María Paula Fletcher asesorando a una clienta en el estudio', ['sizes' => '(max-width: 960px) 100vw, 620px', 'class' => 'fill']) ?></div>
    <div class="stack gap-28">
      <p class="lbl rv c-terracota">Kits Fletcher</p>
      <h2 class="d d-l2 rv">Para retocar<br>y para empezar.</h2>
      <ul class="rv kit-list" role="list">
        <?php foreach ($kits as $k):
            $pending = !App\Repositories\ProductRepository::isPurchasable($k);
            ?>
        <li class="kit-row">
          <div>
            <p class="kit-name"><?= e($k['name']) ?></p>
            <p class="kit-detail"><?= e(rtrim((string) $k['summary'], '.')) ?></p>
          </div>
          <div class="kit-buy">
            <?php if ($pending): ?>
            <span class="kit-price"><span class="pending">[Precio]</span></span>
            <a class="add" href="<?= e(wa_href('tienda-' . $k['slug'], 'tienda')) ?>" rel="nofollow" aria-label="Pedir el <?= e(mb_strtolower($k['name'])) ?> por WhatsApp">Pedir</a>
            <?php else: ?>
            <span class="kit-price"><?= e(money($k['price'])) ?></span>
            <a class="add" href="/tienda/<?= e($k['slug']) ?>" aria-label="Comprar el <?= e(mb_strtolower($k['name'])) ?>">Comprar</a>
            <?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
      <p class="rv fs-14 c-topo">Las alumnas de los cursos tienen descuento especial en la tienda aliada.</p>
    </div>
  </div>
</section>

<section id="productos" class="wrap sec-b">
  <div class="rv sec-head prod-head">
    <div>
      <p class="lbl c-terracota">Recomendados por María Paula</p>
      <h2 class="d d-m">Los que sí funcionan.</h2>
    </div>
    <p class="shop-note sm">Solo productos que usamos en el estudio. Cada uno con una nota de para qué piel sirve.</p>
  </div>
  <div class="g4 prods">
    <?php foreach ($recommended as $i => [$cat, $name, $note]): ?>
    <article class="prod rv" data-delay="<?= e((string) ($i * 0.06)) ?>">
      <div class="tile">
        <img src="<?= e(img_src('marca/isotipo-salmon', 240)) ?>" alt="" width="240" height="240" loading="lazy">
        <span class="tile-tag"><?= e($cat) ?></span>
      </div>
      <h3 class="prod-name"><span class="pending"><?= e($name) ?></span></h3>
      <p class="prod-meta"><span class="pending"><?= e($note) ?></span> · <span class="pending">[Precio]</span></p>
      <a class="add self-start" href="<?= e(wa_href('tienda-recomendado-' . ($i + 1), 'tienda')) ?>" rel="nofollow">Pedir por WhatsApp</a>
    </article>
    <?php endforeach; ?>
  </div>
</section>
