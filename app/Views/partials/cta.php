<?php
/**
 * C12 · bloque de llamada.
 * @var string $label
 * @var string $title
 * @var string $text
 * @var string $button   texto del botón
 * @var string $href
 * @var bool|null $dark      variante .mesh-dark
 * @var bool|null $wa        enlace de WhatsApp (rel nofollow)
 * @var string|null $secondaryText
 * @var string|null $secondaryHref
 */
$dark = !empty($dark);
?>
<section class="wrap sec-b">
  <div class="rv cta <?= $dark ? 'mesh-dark' : 'mesh' ?>">
    <div class="deco" aria-hidden="true"><img src="<?= e(img_src('marca/isotipo-nude', 360)) ?>" alt="" width="360" height="360" loading="lazy"></div>
    <p class="lbl"><?= e($label) ?></p>
    <h2 class="d d-xl"><?= e($title) ?></h2>
    <p class="txt"><?= e($text) ?></p>
    <div class="wrap-row cta-actions">
      <a class="btn<?= $dark ? ' light' : '' ?>" href="<?= e($href) ?>"<?= !empty($wa) ? ' rel="nofollow"' : '' ?>><?= e($button) ?> <?= icon('arrow', 'arr') ?></a>
      <?php if (!empty($secondaryText)): ?>
      <a class="btn <?= $dark ? 'ghost-light' : 'ghost' ?>" href="<?= e($secondaryHref ?? '/reservar') ?>"<?= str_starts_with((string) ($secondaryHref ?? ''), '/wa') ? ' rel="nofollow"' : '' ?>><?= e($secondaryText) ?></a>
      <?php endif; ?>
    </div>
  </div>
</section>
