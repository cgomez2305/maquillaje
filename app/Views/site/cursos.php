<?php
/**
 * Cursos (maqueta Cursos.dc.html).
 * @var list<array<string,mixed>> $cursos
 * @var list<array{q:string,a:string,pending?:bool}> $faq
 * @var list<array{0:string,1:?string}> $crumbs
 */
?>
<section class="bg-tinta">
  <div class="wrap g2 crs-hero">
    <div class="stack gap-40">
      <?= App\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs, 'dark' => true]) ?>
      <h1 class="crs-h1">
        <span class="lbl c-salmon">Cursos de maquillaje en Bucaramanga</span>
        <span class="d crs-title">Aprende<br>a ver la luz.</span>
      </h1>
      <p class="crs-lead mw-480">Dos cursos presenciales en el estudio de Floridablanca: uno para maquillarte tú y otro para hacer del maquillaje tu oficio.</p>
      <div class="wrap-row hide-m">
        <a class="btn light" href="#automaquillaje">Automaquillaje</a>
        <a class="btn ghost-light" href="#profesional">Maquillaje profesional</a>
      </div>
      <nav class="crs-cmp only-m" aria-label="Comparar cursos">
        <?php foreach ($cursos as $c): ?>
        <a href="#<?= e($c['id']) ?>">
          <span class="lbl"><?= e($c['label']) ?></span>
          <span class="crs-cmp-n"><?= e($c['title']) ?></span>
          <span class="crs-cmp-p"><?= e(money($c['price'], false)) ?> <small>COP</small></span>
          <span class="crs-cmp-go">Ver contenido <?= icon('arrow', 'arr') ?></span>
        </a>
        <?php endforeach; ?>
      </nav>
    </div>
    <div class="ph px ar-45 m-43 arch-soft crs-hero-ph">
      <?= img('fotos/catalogo-maria-paula-tablero', 'María Paula Fletcher frente al tablero durante una clase de maquillaje', ['priority' => true, 'sizes' => '(max-width: 960px) 100vw, 560px', 'class' => 'fill']) ?>
    </div>
  </div>
</section>

<section class="wrap sec-t">
  <div class="g3 crs-pillars">
    <div><p class="d">En el estudio</p><p class="crs-pillar-txt">Clases presenciales en el estudio de la <?= e(config('app.business.address')) ?>, Floridablanca.</p></div>
    <div><p class="d">Práctica real</p><p class="crs-pillar-txt">Con la misma luz y los mismos productos del estudio.</p></div>
    <div><p class="d">Empiezas ya</p><p class="crs-pillar-txt">Lista de materiales con descuento en tienda aliada, o empiezas con el kit de María Paula.</p></div>
  </div>
</section>

<section class="wrap crs-list">
  <?php foreach ($cursos as $c): ?>
  <article id="<?= e($c['id']) ?>" class="course">
    <div class="stack gap-28">
      <h2 class="d d-l"><?= e($c['title']) ?></h2>
      <p class="body-l crs-desc"><?= e($c['text']) ?></p>
      <p class="crs-price"><span class="d"><?= e(money($c['price'], false)) ?></span><span class="c-topo fs-15">COP</span></p>
      <div class="wrap-row">
        <a class="btn" href="<?= e($c['buy']) ?>" rel="nofollow"><?= icon('whatsapp') ?> Inscribirme por WhatsApp</a>
      </div>
    </div>
    <div>
      <p class="lbl c-topo crs-mods-lbl"><?= e($c['modules_label']) ?></p>
      <ol class="crs-mods">
        <?php foreach ($c['modules'] as $i => $m): ?>
        <li class="mod"><span><?= e(sprintf('%02d', $i + 1)) ?></span><span><?= e($m) ?></span></li>
        <?php endforeach; ?>
      </ol>
      <?php if ($c['note']): ?><p class="crs-note"><?= e($c['note']) ?></p><?php endif; ?>
    </div>
  </article>
  <?php endforeach; ?>
</section>

<section class="wrap sec">
  <div class="g2">
    <div class="ph px ar-45 m-43 op-top"><?= img('fotos/proceso-brocha-bn', 'María Paula Fletcher sonriendo mientras maquilla a una novia con brocha, en blanco y negro', ['sizes' => '(max-width: 960px) 100vw, 620px', 'class' => 'fill']) ?></div>
    <div class="stack gap-28">
      <h2 class="d d-m">María Paula Fletcher</h2>
      <p class="body-l mw-480">Creadora del equipo Make Up by Fletcher (MUBF). Maquilladora profesional especializada en novias y quinceañeras, con énfasis en acabado natural y duradero. Enseña lo mismo que aplica cada semana en su estudio.</p>
    </div>
  </div>
</section>

<section class="bg-arena sec">
  <div class="wrap g2 crs-faq">
    <div class="stack gap-18">
      <h2 class="d d-l2">Sobre<br>los cursos.</h2>
    </div>
    <div class="faq">
      <?php foreach ($faq as $i => $item): ?>
      <details<?= $i === 0 ? ' open' : '' ?>>
        <summary><span><?= e($item['q']) ?></span><?= icon('plus', 'pl') ?></summary>
        <div><p><?php if (!empty($item['pending'])): ?><span class="pending"><?= e($item['a']) ?></span><?php else: ?><?= e($item['a']) ?><?php endif; ?></p></div>
      </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="wrap sec">
  <div class="cta mesh crs-cta">
    <div class="deco crs-deco" aria-hidden="true"><img src="<?= e(img_src('marca/isotipo-nude', 360)) ?>" alt="" width="360" height="360" loading="lazy"></div>
    <h2 class="d d-xl">Reserva tu cupo.</h2>
    <div class="wrap-row cta-actions">
      <a class="btn" href="<?= e(wa_href('cursos-cta', 'cursos')) ?>" rel="nofollow">Escribir por WhatsApp <?= icon('arrow', 'arr') ?></a>
    </div>
  </div>
</section>
