<?php /** @var bool $first @var list<array{0:string,1:?string}> $crumbs */ ?>
<section class="wrap sec guide-done">
  <?= App\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
  <div class="stack gap-28 mw-620 guide-done-in">
    <p class="lbl c-terracota in">Guía gratuita para novias</p>
    <h1 class="d d-l in d1"><?= $first ? 'Listo, tu correo quedó confirmado.' : 'Tu correo ya estaba confirmado.' ?></h1>
    <p class="lead in d2"><?= $first ? 'Te enviamos la guía de belleza para novias. Revisa tu bandeja de entrada en los próximos minutos.' : 'Ya te habíamos enviado la guía. Si no la encuentras, revisa la carpeta de promociones o de correo no deseado.' ?></p>
    <div class="wrap-row in d3">
      <a class="btn" href="/maquillaje-de-novia-bucaramanga">Conocer la experiencia novia</a>
      <a class="btn ghost" href="/diario">Volver al diario</a>
    </div>
  </div>
</section>
