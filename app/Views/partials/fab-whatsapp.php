<?php
/**
 * C14 · contacto rápido.
 * - Escritorio: botón flotante de WhatsApp.
 * - Móvil (≤ 960 px): barra de acción fija con el precio de referencia de la página, WhatsApp y Reservar,
 *   para que la clienta tenga siempre a mano la decisión sin volver a buscar el botón.
 * @var string $origin
 * @var string $path
 */
$packs = App\Domain\Catalog::packs();
$courses = App\Domain\Catalog::courses();
$from = static fn(array $items): int => min(array_map(static fn(array $p): int => (int) $p['price'], $items));
$bars = [
    '/maquillaje-de-novia-bucaramanga' => ['Pack novias', money($packs['pack3']['price']), '/reservar?servicio=pack3', 'Reservar'],
    '/cursos-de-maquillaje-bucaramanga' => ['Cursos desde', money($from($courses)), '#automaquillaje', 'Ver cursos'],
    '/tienda' => ['Tienda', 'Próximamente', wa_href('barra-tienda', 'bono'), 'Pedir bono'],
];
[$noteLabel, $noteValue, $bookHref, $bookText] = $bars[$path ?? ''] ?? ['Servicios desde', money($from($packs)), '/reservar', 'Reservar'];
$wa = wa_href('fab-' . ($origin !== '' ? $origin : 'inicio'));
?>
<a class="fab" href="<?= e($wa) ?>" rel="nofollow" aria-label="Escribir por WhatsApp para reservar" data-wa>
  <?= icon('whatsapp') ?>
  <span>Reservar</span>
</a>
<div class="mbar only-m">
  <p class="mbar-note"><span class="mbar-k"><?= e($noteLabel) ?></span><span class="mbar-v"><?= e($noteValue) ?></span></p>
  <a class="mbar-wa" href="<?= e($wa) ?>" rel="nofollow" aria-label="Escribir por WhatsApp" data-wa><?= icon('whatsapp') ?></a>
  <a class="btn mbar-btn" href="<?= e($bookHref) ?>"><?= e($bookText) ?> <?= icon('arrow', 'arr') ?></a>
</div>
