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

<?php
$packs = App\Domain\Catalog::packs();
$courses = App\Domain\Catalog::courses();
$b = config('app.business');
// Mosaicos de servicio: decidir con foto y precio a la vista
$tiles = [
    ['/maquillaje-de-novia-bucaramanga', 'Novias y quinceañeras', money($packs['pack3']['price'], false), 'fotos/novia-tiara-ramo'],
    ['/precios-maquillaje-bucaramanga#packs', 'Social y grados', money($packs['pack1']['price'], false), 'fotos/social-cumple-pastel'],
    ['/precios-maquillaje-bucaramanga#domicilio', 'A domicilio', money($packs['pack2']['price'], false), 'fotos/proceso-domicilio'],
    ['/cursos-de-maquillaje-bucaramanga', 'Cursos', 'desde ' . money(min(array_map(static fn(array $c): int => (int) $c['price'], $courses)), false), 'fotos/proceso-brocha-bn'],
];
$rows = [
    ['/', 'Inicio', 'Make Up by Fletcher', 'home'],
    ['/precios-maquillaje-bucaramanga', 'Servicios y precios', 'Compara los tres packs', 'tag'],
    ['/portafolio', 'Portafolio', 'Novias, social y detrás de cámaras', 'image'],
    ['/tienda', 'Tienda', 'Próximamente · bonos y kits', 'bag'],
    ['/diario', 'Diario', 'Guías de maquillaje', 'book'],
    ['/estudio-maquillaje-floridablanca', 'Estudio', 'Floridablanca · cómo llegar', 'pin'],
];
?>
<div class="sheet" id="menu" role="dialog" aria-modal="true" aria-label="Menú">
  <div class="sheet-top">
    <a class="brand" href="/" aria-label="Make Up by Fletcher, inicio"><?= $logo ?><span class="d">Fletcher</span></a>
    <a class="btn ghost icon-only" href="#" role="button" aria-label="Cerrar menú" data-menu-close><?= icon('close') ?></a>
  </div>
  <div class="sheet-body">
    <p class="lbl c-terracota sheet-lbl">¿Qué necesitas?</p>
    <nav class="mtiles" aria-label="Servicios">
      <?php foreach ($tiles as [$href, $label, $price, $photo]): ?>
      <a class="mtile" href="<?= e($href) ?>"<?= str_contains($href, '#') ? '' : current_attr($href, $path) ?>>
        <?= img($photo, '', ['sizes' => '45vw', 'width' => 480]) ?>
        <span class="mtile-t"><span class="mtile-n"><?= e($label) ?></span><span class="mtile-p"><?= e($price) ?> <small>COP</small></span></span>
      </a>
      <?php endforeach; ?>
    </nav>
    <p class="lbl c-topo sheet-lbl">Explora</p>
    <nav class="mrows" aria-label="Menú móvil">
      <?php foreach ($rows as [$href, $label, $hint, $ic]): ?>
      <a class="mrow" href="<?= e($href) ?>"<?= current_attr($href, $path) ?>>
        <span class="mrow-i"><?= icon($ic) ?></span>
        <span class="mrow-t"><span class="mrow-n"><?= e($label) ?></span><span class="mrow-h"><?= e($hint) ?></span></span>
        <?= icon('chevron', 'ic mrow-c') ?>
      </a>
      <?php endforeach; ?>
    </nav>
    <div class="msocial">
      <a href="<?= e($b['instagram']) ?>" target="_blank" rel="noopener" aria-label="Instagram de Make Up by Fletcher"><?= icon('instagram') ?></a>
      <a href="<?= e($b['tiktok']) ?>" target="_blank" rel="noopener" aria-label="TikTok de Make Up by Fletcher"><?= icon('tiktok') ?></a>
      <a href="mailto:<?= e($b['email']) ?>" aria-label="Escribir un correo"><?= icon('mail') ?></a>
      <p class="msocial-t">Agenda 2026 · 2027<br><span><?= e($b['city']) ?>, <?= e($b['region']) ?></span></p>
    </div>
  </div>
  <div class="sheet-cta">
    <a class="btn" href="/reservar"><?= icon('calendar') ?> Reservar fecha</a>
    <a class="btn ghost" href="<?= e(wa_href('menu')) ?>" rel="nofollow"><?= icon('whatsapp') ?> WhatsApp</a>
  </div>
</div>
