<?php
/** C02 cabecera de vidrio + C03 menú móvil. @var string $path */
$links = [
    ['/maquillaje-de-novia-bucaramanga', 'Novias'],
    ['/precios-maquillaje-bucaramanga', 'Servicios y precios'],
    ['/cursos-de-maquillaje-bucaramanga', 'Cursos'],
    ['/portafolio', 'Portafolio'],
    ['/tienda', 'Tienda'],
    ['/diario', 'Diario'],
    ['/estudio-maquillaje-floridablanca', 'Estudio'],
];
$logo = '<img src="' . img_src('marca/isotipo-negativo', 80) . '" alt="" width="40" height="40">';
?>
<header class="nav">
  <div class="wrap nav-in">
    <a class="brand" href="/" aria-label="Make Up by Fletcher, inicio"><?= $logo ?><span class="d">Fletcher</span></a>
    <nav class="nav-links" aria-label="Principal">
      <?php foreach ($links as [$href, $label]): ?>
      <a href="<?= e($href) ?>"<?= current_attr($href, $path) ?>><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="nav-actions">
      <a class="btn sm hide-m" href="/reservar">Reservar fecha</a>
      <a class="btn ghost icon-only menu-btn" href="#menu" role="button" aria-controls="menu" aria-expanded="false" aria-label="Abrir menú" data-menu-open><?= icon('menu') ?></a>
    </div>
  </div>
</header>

<div class="sheet" id="menu" role="dialog" aria-modal="true" aria-label="Menú">
  <div class="sheet-top">
    <a class="brand" href="/" aria-label="Make Up by Fletcher, inicio"><?= $logo ?><span class="d">Fletcher</span></a>
    <a class="btn ghost icon-only" href="#" role="button" aria-label="Cerrar menú" data-menu-close><?= icon('close') ?></a>
  </div>
  <nav aria-label="Menú móvil">
    <a href="/"<?= current_attr('/', $path) ?>>Inicio</a>
    <?php foreach ($links as [$href, $label]): ?>
    <a href="<?= e($href) ?>"<?= current_attr($href, $path) ?>><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="sheet-cta">
    <a class="btn" href="/reservar">Reservar fecha</a>
    <a class="btn ghost" href="<?= e(wa_href('menu')) ?>" rel="nofollow">Escribir por WhatsApp</a>
  </div>
</div>
