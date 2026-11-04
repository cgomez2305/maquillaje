<?php
/**
 * Diario (maqueta Blog.dc.html).
 * @var array<string,mixed>|null $featured
 * @var list<array<string,mixed>> $posts
 * @var list<string> $topics
 * @var list<array{title:string,category:string}> $upcoming
 * @var array{ok:?string,errors:array<string,string>,old:array<string,string>} $guide
 * @var list<array{0:string,1:?string}> $crumbs
 */
$err = $guide['errors'];
$old = $guide['old'];
$fieldErr = static function (string $k) use ($err): string {
    return isset($err[$k]) ? '<p class="err" id="err-' . e($k) . '">' . e($err[$k]) . '</p>' : '';
};
$aria = static fn(string $k): string => isset($err[$k]) ? ' aria-invalid="true" aria-describedby="err-' . e($k) . '"' : '';
?>
<section class="wrap blog-hero">
  <?= App\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
  <div class="blog-hero-row">
    <h1 class="hero-h1">
      <span class="lbl in c-terracota">Diario de belleza · Blog de maquillaje</span>
      <span class="d blog-h1 in d1">Lo que aprendemos<br>en la silla.</span>
    </h1>
    <p class="body-l blog-intro in d2">Guías honestas sobre maquillaje de novia, grados, cuidado de la piel y automaquillaje, escritas por María Paula Fletcher desde el estudio.</p>
  </div>
</section>

<?php if ($featured !== null): ?>
<section class="wrap sec-b">
  <a class="post feat in d3" href="/diario/<?= e($featured['slug']) ?>">
    <div class="ph feat-ph op-35"><?= img((string) $featured['cover'], (string) $featured['title'], ['sizes' => '(max-width: 960px) 100vw, 720px', 'priority' => true]) ?></div>
    <div class="stack gap-22">
      <p class="lbl c-terracota">Destacado · <?= e($featured['category']) ?></p>
      <h2 class="d feat-title"><?= e($featured['title']) ?></h2>
      <p class="body-l"><?= e($featured['excerpt']) ?></p>
      <div class="meta"><span>María Paula Fletcher</span><span><?= e((string) $featured['reading_minutes']) ?> min de lectura</span></div>
      <span class="link-line">Leer la guía <?= icon('arrow', 'arr') ?></span>
    </div>
  </a>
</section>
<?php endif; ?>

<?php if ($posts !== []): ?>
<section class="wrap sec-b">
  <div class="all-head">
    <h2 class="d all-title">Todas las guías</h2>
    <?php if (count($topics) > 1): ?>
    <div class="chips" role="group" aria-label="Filtrar por tema" data-filter-chips hidden>
      <button type="button" class="chip on" aria-pressed="true" data-filter="Todo">Todo</button>
      <?php foreach ($topics as $t): ?>
      <button type="button" class="chip" aria-pressed="false" data-filter="<?= e($t) ?>"><?= e($t) ?></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
  <div class="posts" data-filter-list aria-live="polite">
    <?php foreach ($posts as $p): ?>
    <a class="post" href="/diario/<?= e($p['slug']) ?>" data-cat="<?= e($p['category']) ?>">
      <div class="ph ar-45"><?= img((string) $p['cover'], (string) $p['title'], ['sizes' => '(max-width: 960px) 100vw, 420px']) ?><span class="tag"><?= e($p['category']) ?></span></div>
      <h3 class="d post-title"><?= e($p['title']) ?></h3>
      <p class="excerpt"><?= e($p['excerpt']) ?></p>
      <div class="meta"><span><?= e($p['target_keyword']) ?></span><span><?= e((string) $p['reading_minutes']) ?> min</span></div>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php elseif ($upcoming !== []): ?>
<section class="wrap sec-b">
  <div class="all-head">
    <h2 class="d all-title">Próximas guías</h2>
    <p class="body-l mw-460">Estamos escribiendo estas guías desde el estudio. Recibe la guía para novias mientras tanto.</p>
  </div>
  <details class="upcoming-d">
    <summary><span>Ver las <?= count($upcoming) ?> guías en preparación</span><?= icon('plus', 'pl') ?></summary>
    <ul class="upcoming">
      <?php foreach ($upcoming as $u): ?>
      <li><span class="lbl c-terracota"><?= e($u['category']) ?></span><span class="up-title"><?= e($u['title']) ?></span></li>
      <?php endforeach; ?>
    </ul>
  </details>
</section>
<?php endif; ?>

<section class="wrap sec-b" id="guia">
  <div class="nl rv mesh-dark">
    <div class="nl-deco" aria-hidden="true"><img src="<?= e(img_src('marca/isotipo-nude', 360)) ?>" alt="" width="360" height="360" loading="lazy"></div>
    <div class="stack gap-22 nl-txt">
      <p class="lbl c-salmon">Guía gratuita para novias</p>
      <h2 class="d nl-title">Tu checklist de belleza, mes a mes.</h2>
      <p class="nl-p">Qué hacer con la piel, la prueba de maquillaje y el día de la boda, en una sola página. Te la enviamos al correo.</p>
    </div>
    <?php if ($guide['ok']): ?>
    <div class="nl-ok" role="status">
      <p class="lbl c-salmon">Revisa tu correo</p>
      <p class="d nl-ok-title">Te escribimos a <?= e($guide['ok']) ?>.</p>
      <p class="nl-p">Abre el correo y confirma tu dirección para recibir la guía. Si no lo ves en unos minutos, revisa la carpeta de promociones o de correo no deseado.</p>
    </div>
    <?php else: ?>
    <form class="nl-form" method="post" action="/diario/guia-novias" aria-label="Recibir la guía gratuita" novalidate>
      <?= csrf_field() ?>
      <?= spam_fields() ?>
      <?php if (isset($err['form'])): ?><p class="notice err" role="alert"><?= e($err['form']) ?></p><?php endif; ?>
      <div class="f on-dark">
        <label for="nl-nombre">Nombre</label>
        <input id="nl-nombre" name="nombre" type="text" placeholder="Tu nombre" autocomplete="name" maxlength="120" value="<?= e($old['nombre'] ?? '') ?>">
      </div>
      <div class="f on-dark<?= isset($err['correo']) ? ' has-err' : '' ?>">
        <label for="nl-correo">Correo</label>
        <input id="nl-correo" name="correo" type="email" placeholder="tu@correo.com" autocomplete="email" required maxlength="190" value="<?= e($old['correo'] ?? '') ?>"<?= $aria('correo') ?>>
        <?= $fieldErr('correo') ?>
      </div>
      <div class="f on-dark<?= isset($err['fecha_boda']) ? ' has-err' : '' ?>">
        <label for="nl-fecha">Fecha de tu boda</label>
        <input id="nl-fecha" name="fecha_boda" type="date" required min="<?= e(local_time(now_utc())?->format('Y-m-d')) ?>" value="<?= e($old['fecha_boda'] ?? '') ?>"<?= $aria('fecha_boda') ?>>
        <?= $fieldErr('fecha_boda') ?>
      </div>
      <div class="nl-ckwrap">
        <label class="ckb nl-ck"><input type="checkbox" name="acepto" value="1" required<?= $aria('acepto') ?>> <span>Autorizo el tratamiento de mis datos para recibir la guía y novedades, según la <a href="/politica-de-privacidad">política de datos</a> (Ley 1581 de 2012).</span></label>
        <?= $fieldErr('acepto') ?>
      </div>
      <button class="btn salmon self-start" type="submit">Enviarme la guía <?= icon('arrow', 'arr') ?></button>
    </form>
    <?php endif; ?>
  </div>
</section>

<?= App\View::partial('partials/cta', [
    'label' => 'Agenda 2026 · 2027',
    'title' => 'Reserva tu fecha.',
    'text' => 'Cuéntanos el día, la hora y el tipo de evento. Te respondemos con disponibilidad y la propuesta para ti.',
    'button' => 'Reservar en línea',
    'href' => '/reservar',
    'secondaryText' => 'Escribir por WhatsApp',
    'secondaryHref' => wa_href('diario-cta'),
]) ?>
