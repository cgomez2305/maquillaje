<?php
/**
 * Layout público.
 * @var string $content
 * @var array{title:string,description:string,canonical?:string,robots?:string,og_image?:string,og_type?:string} $meta
 * @var list<array{0:string,1:?string}>|null $crumbs
 * @var list<string>|null $css   hojas extra (pages/x.css)
 * @var list<string>|null $js    módulos extra (x.js)
 * @var list<array>|null $schema JSON-LD adicional
 * @var array{key:string,sizes:string}|null $preload imagen LCP
 * @var string|null $path
 */
$path = $path ?? '/';
$canonical = url($meta['canonical'] ?? $path);
$ogImage = $meta['og_image'] ?? url('/assets/img/og-default.jpg');
$nonce = csp_nonce();
$b = config('app.business');
$schemaBlocks = [App\Support\Schema::business()];
if (!empty($crumbs)) {
    $schemaBlocks[] = App\Support\Schema::breadcrumbs($crumbs);
}
foreach ($schema ?? [] as $block) {
    $schemaBlocks[] = $block;
}
?><!doctype html>
<html lang="es-CO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($meta['title']) ?></title>
<meta name="description" content="<?= e($meta['description']) ?>">
<link rel="canonical" href="<?= e($canonical) ?>">
<meta name="robots" content="<?= e($meta['robots'] ?? 'index, follow, max-image-preview:large') ?>">
<meta name="theme-color" content="#FBF8F5">
<meta property="og:locale" content="es_CO">
<meta property="og:site_name" content="Make Up by Fletcher">
<meta property="og:type" content="<?= e($meta['og_type'] ?? 'website') ?>">
<meta property="og:title" content="<?= e($meta['title']) ?>">
<meta property="og:description" content="<?= e($meta['description']) ?>">
<meta property="og:url" content="<?= e($canonical) ?>">
<meta property="og:image" content="<?= e($ogImage) ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($meta['title']) ?>">
<meta name="twitter:description" content="<?= e($meta['description']) ?>">
<meta name="twitter:image" content="<?= e($ogImage) ?>">
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/assets/img/favicon-32.png" type="image/png">
<link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">
<link rel="preload" href="/assets/fonts/comfortaa-var.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/assets/fonts/dm-sans-var.woff2" as="font" type="font/woff2" crossorigin>
<?php if (!empty($preload)): ?>
<?= App\Support\Schema::preloadImage($preload['key'], $preload['sizes'] ?? '100vw') ?>
<?php endif; ?>
<link rel="stylesheet" href="<?= e(asset('css/tokens.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/components.css')) ?>">
<?php foreach ($css ?? [] as $sheet): ?>
<link rel="stylesheet" href="<?= e(asset('css/' . $sheet)) ?>">
<?php endforeach; ?>
<script nonce="<?= e($nonce) ?>">
(function(d){var h=d.documentElement;if(window.matchMedia&&matchMedia('(prefers-reduced-motion: no-preference)').matches){h.classList.add('js');setTimeout(function(){if(!window.__mubf)h.classList.remove('js')},2500)}})(document);
</script>
<?php foreach ($schemaBlocks as $block): ?>
<?= json_ld($block) ?>
<?php endforeach; ?>
</head>
<body>
<a class="skip" href="#contenido">Saltar al contenido</a>
<div class="page">
<?= App\View::partial('partials/header', ['path' => $path]) ?>
<main id="contenido">
<?= $content ?>
</main>
<?= App\View::partial('partials/footer', []) ?>
<?php if (empty($hideFab)): ?>
<?= App\View::partial('partials/fab-whatsapp', ['origin' => $waOrigin ?? ltrim($path, '/')]) ?>
<?php endif; ?>
</div>
<?= App\View::partial('partials/consent', []) ?>
<script src="<?= e(asset('vendor/gsap.min.js')) ?>" defer></script>
<script src="<?= e(asset('vendor/ScrollTrigger.min.js')) ?>" defer></script>
<script type="module" src="<?= e(asset('js/app.js')) ?>"></script>
<?php foreach ($js ?? [] as $script): ?>
<script type="module" src="<?= e(asset('js/' . $script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
