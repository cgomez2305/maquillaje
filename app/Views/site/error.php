<?php /** @var int $status @var string $title @var string $text */ ?>
<section class="wrap sec error-page">
  <div class="stack gap-28 mw-620">
    <p class="lbl c-terracota in">Error <?= e((string) $status) ?></p>
    <h1 class="d d-l in d1"><?= e($title) ?></h1>
    <p class="lead in d2"><?= e($text) ?></p>
    <div class="wrap-row in d3">
      <a class="btn" href="/">Volver al inicio</a>
      <a class="btn ghost" href="<?= e(wa_href('error-' . $status)) ?>" rel="nofollow">Escribir por WhatsApp</a>
    </div>
  </div>
</section>
