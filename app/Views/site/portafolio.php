<?php
/**
 * Portafolio (maqueta Portafolio.dc.html).
 * @var list<array{key:string,cat:string,cat_label:string,alt:string,crop:string,full:string}> $items
 * @var array<string,string> $cats
 * @var list<array{0:string,1:?string}> $crumbs
 */
?>
<section class="wrap pf-head">
  <?= App\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
  <div class="pf-head-row">
    <h1 class="pf-h1">
      <span class="lbl c-terracota">Portafolio de maquillaje · Bucaramanga</span>
      <span class="d pf-title">El trabajo<br>habla bajito.</span>
    </h1>
    <p class="pf-lead">Fotos de clientas reales, sin filtros de piel. Toca una imagen para verla en grande.</p>
  </div>
</section>

<section class="wrap sec-b">
  <div class="chips pf-chips" role="group" aria-label="Filtrar por tipo de servicio" data-pf-chips hidden>
    <button type="button" class="chip on" aria-pressed="true" data-cat="todo" data-label="Todo">Todo <span class="chip-c"><?= count($items) ?></span></button>
    <?php $n = 0; foreach ($cats as $slug => $label): $n++; $count = count(array_filter($items, static fn(array $it): bool => $it['cat'] === $slug)); ?>
    <button type="button" class="chip" aria-pressed="false" data-cat="<?= e($slug) ?>" data-label="<?= e($label) ?>"><span class="chip-n" aria-hidden="true"><?= $n ?></span><?= e($label) ?> <span class="chip-c"><?= $count ?></span></button>
    <?php endforeach; ?>
  </div>
  <p class="sr-only" aria-live="polite" data-pf-status></p>
  <div class="pf-grid" data-pf-grid>
    <?php foreach ($items as $n => $it): ?>
    <a class="it" href="<?= e($it['full']) ?>" data-cat="<?= e($it['cat']) ?>" data-label="<?= e($it['cat_label']) ?>" aria-label="<?= e($it['cat_label']) ?>: ampliar foto. <?= e($it['alt']) ?>">
      <span class="ph<?= $it['crop'] !== '' ? ' ' . e($it['crop']) : '' ?>"><?= img($it['key'], $it['alt'], ['sizes' => '(max-width: 960px) 50vw, 420px', 'eager' => $n < 4, 'priority' => $n === 0]) ?></span>
      <span class="lbl c-topo pf-cap"><?= e($it['cat_label']) ?></span>
    </a>
    <?php endforeach; ?>
  </div>
  <div class="pf-more">
    <a class="btn ghost" href="<?= e(config('app.business.instagram')) ?>" rel="noopener" target="_blank">Más trabajos en Instagram</a>
  </div>
</section>

<div class="lb" role="dialog" aria-modal="true" aria-label="Imagen ampliada" data-pf-lb hidden>
  <button type="button" class="x" aria-label="Cerrar" data-pf-close><?= icon('close') ?></button>
  <figure class="lb-card" data-pf-card>
    <span class="lb-media" data-pf-media></span>
    <figcaption class="lbl" data-pf-cap></figcaption>
  </figure>
</div>
