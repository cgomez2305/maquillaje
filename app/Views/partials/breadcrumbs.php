<?php
/**
 * Migas visibles (el JSON-LD BreadcrumbList lo genera el layout a partir de $crumbs).
 * @var list<array{0:string,1:?string}> $crumbs
 * @var bool|null $dark
 * @var string|null $class
 */
?>
<nav aria-label="Ruta" class="crumbs<?= !empty($dark) ? ' on-dark' : '' ?> <?= e($class ?? '') ?>">
  <?php foreach ($crumbs as $i => [$label, $href]): ?>
    <?php if ($i > 0): ?><span aria-hidden="true">/</span><?php endif; ?>
    <?php if ($href !== null): ?><a href="<?= e($href) ?>"><?= e($label) ?></a><?php else: ?><span aria-current="page"><?= e($label) ?></span><?php endif; ?>
  <?php endforeach; ?>
</nav>
