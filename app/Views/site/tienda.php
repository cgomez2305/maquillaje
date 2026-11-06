<?php
/**
 * Tienda · próximamente (sin catálogo ni pagos en línea).
 * @var list<array{0:string,1:?string}> $crumbs
 */
$soon = [
    ['Bonos de regalo', 'Para un cumpleaños, un grado o una despedida de soltera: una experiencia de maquillaje con el nombre de quien la recibe.', 'fotos/social-cumple-globos', 'Clienta con vestido verde y globos dorados celebrando su cumpleaños'],
    ['Kits Fletcher', 'El kit de retoque que entregamos a las novias y un kit de inicio para las alumnas de los cursos.', 'fotos/proceso-labios-detalle', 'Detalle de labios con brillo y pincel de labial'],
    ['Recomendados', 'Solo los productos que usamos cada semana en el estudio, con una nota de para qué piel sirve cada uno.', 'fotos/catalogo-paleta', 'Paleta de correctores y pincel'],
];
?>
<section class="wrap shop-hero">
  <?= App\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
  <div class="shop-hero-grid">
    <h1 class="hero-title">
      <span class="soon-badge"><span class="soon-dot" aria-hidden="true"></span>Tienda · Próximamente</span>
      <span class="d shop-h1">Muy pronto,<br>en tu piel.</span>
    </h1>
    <div class="stack gap-28">
      <p class="lead shop-hero-p">Estamos preparando la tienda de Make Up by Fletcher: bonos de regalo, kits y los productos que María Paula usa cada semana en el estudio.</p>
      <div class="wrap-row">
        <a class="btn" href="<?= e(wa_href('tienda-bono', 'bono')) ?>" rel="nofollow"><?= icon('whatsapp') ?> Pedir un bono de regalo</a>
        <a class="btn ghost" href="<?= e(wa_href('tienda-aviso', 'tienda')) ?>" rel="nofollow">Avísame cuando abra</a>
      </div>
    </div>
  </div>
</section>

<section class="wrap sec-b" aria-labelledby="soon-t">
  <h2 class="sr-only" id="soon-t">Lo que viene</h2>
  <ul class="g3 m-snap soon" role="list">
    <?php foreach ($soon as $i => [$title, $text, $photo, $alt]): ?>
    <li class="soon-card">
      <div class="ph ar-45"><?= img($photo, $alt, ['sizes' => '(max-width: 960px) 80vw, 420px', 'eager' => $i === 0]) ?><span class="tag">Muy pronto</span></div>
      <h3 class="d soon-title"><?= e($title) ?></h3>
      <p class="soon-text"><?= e($text) ?></p>
    </li>
    <?php endforeach; ?>
  </ul>
  <p class="m-snap-hint only-m" aria-hidden="true">Desliza para ver más <?= icon('arrow', 'arr') ?></p>
</section>

<section class="wrap sec-b">
  <div class="cta mesh soon-cta">
    <div class="deco" aria-hidden="true"><img src="<?= e(img_src('marca/isotipo-nude', 360)) ?>" alt="" width="360" height="360" loading="lazy"></div>
    <h2 class="d d-l">¿Lo necesitas ya?</h2>
    <p class="txt">Pídelo por WhatsApp: lo preparamos en tarjeta física o digital con el nombre de quien lo recibe.</p>
    <div class="wrap-row cta-actions">
      <a class="btn" href="<?= e(wa_href('tienda-cta', 'bono')) ?>" rel="nofollow"><?= icon('whatsapp') ?> Pedir por WhatsApp</a>
      <a class="btn ghost" href="/precios-maquillaje-bucaramanga">Ver servicios y precios</a>
    </div>
  </div>
</section>
