<?php
/**
 * C09 · acordeón de preguntas. El JSON-LD FAQPage lo añade el controlador con Schema::faq($items).
 * @var list<array{q:string,a:string}> $items  (a: texto plano; los párrafos se separan con \n\n)
 * @var bool|null $openFirst
 * @var string|null $class  'sm' para la variante compacta del artículo
 */
?>
<div class="faq <?= e($class ?? '') ?>">
  <?php foreach ($items as $i => $item): ?>
  <details<?= ($i === 0 && ($openFirst ?? true)) ? ' open' : '' ?>>
    <summary><span><?= e($item['q']) ?></span><?= icon('plus', 'pl') ?></summary>
    <div><?php foreach (preg_split('/\n{2,}/', $item['a']) ?: [] as $para): ?><p><?= e($para) ?></p><?php endforeach; ?></div>
  </details>
  <?php endforeach; ?>
</div>
