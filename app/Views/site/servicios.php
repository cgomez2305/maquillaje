<?php
/**
 * Servicios y precios (maqueta Servicios.dc.html).
 * @var array<string,array{name:string,short:string,price:int,includes:list<string>}> $packs
 * @var list<array{0:string,1:?string}> $crumbs
 */
$check = '<svg class="ck" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M3 9.5l4 4 8-9"/></svg>';
$cards = [
    'pack1' => ['label' => 'Servicio base', 'title' => 'Maquillaje social', 'note' => 'Grados, cenas, sesiones de fotos e invitadas de boda. En el estudio de Floridablanca.', 'cta' => 'Reservar Pack 1', 'href' => '/reservar?servicio=pack1', 'wa' => 'social',
        'pills' => ['Asesoría previa', 'Maquillaje social', 'Pestañas incluidas'], 'photo' => 'fotos/social-cumple-pastel', 'alt' => 'Clienta con maquillaje social de ojos dorados sonriendo con su torta de cumpleaños'],
    'pack2' => ['label' => 'Full experience · A domicilio', 'title' => 'El estudio en tu casa', 'note' => 'Una sesión premium en la comodidad de tu casa, con música relajante y ventilador de mano para que estés fresca.', 'cta' => 'Reservar Pack 2', 'href' => '/reservar?servicio=pack2', 'wa' => 'domicilio',
        'pills' => ['Skin care y masaje', 'Silla y luz de estudio', 'Pestañas incluidas', 'Kit de retoque'], 'photo' => 'fotos/proceso-domicilio', 'alt' => 'Maquilladora aplicando rubor a una novia en bata en su casa'],
    'pack3' => ['label' => 'Novias y quinceañeras', 'title' => 'Tu gran día', 'note' => 'Bodas, quince años y cualquier día que merezca prueba previa.', 'cta' => 'Reservar Pack 3', 'href' => '/reservar?servicio=pack3', 'wa' => 'novias',
        'pills' => ['Prueba previa', 'Dos propuestas', 'Video reel', 'Extra fijación'], 'photo' => 'fotos/novia-tiara-ramo', 'alt' => 'Novia con tiara y recogido alto sonriendo con su ramo de hortensias'],
];
$rows = [
    ['Asesoría previa y maquillaje social', [1, 1, 1]],
    ['Pestañas postizas', [1, 1, 1]],
    ['Skin care y masaje relajante', [0, 1, 1]],
    ['Silla profesional y luz de estudio', [0, 1, 1]],
    ['Kit de retoque', [0, 1, 1]],
    ['Prueba previa', [0, 0, 1]],
    ['Dos propuestas', [0, 0, 1]],
    ['Video reel', [0, 0, 1]],
    ['Extra fijación', [0, 0, 1]],
];
?>
<section class="wrap sv-hero">
  <?= App\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
  <div class="g2 sv-hero-grid">
    <h1 class="sv-h1">
      <span class="lbl in c-terracota">Precios de maquillaje en Bucaramanga · Catálogo 2026</span>
      <span class="d sv-hero-d in d1">Elige tu<br>experiencia.</span>
    </h1>
    <p class="lead sv-hero-p in d2">Tres formatos con precio cerrado. En el estudio de Floridablanca o en tu casa, en todo el área metropolitana de Bucaramanga.</p>
  </div>
  <nav class="sv-strip only-m in d3" aria-label="Ir a un pack">
    <?php foreach (['pack1' => 'Social', 'pack2' => 'A domicilio', 'pack3' => 'Novias'] as $code => $short): ?>
    <a href="#pk-<?= e($code) ?>"><span class="lbl"><?= e('Pack 0' . substr($code, -1)) ?></span><span class="sv-strip-n"><?= e($short) ?></span><span class="sv-strip-p"><?= e(money($packs[$code]['price'], false)) ?></span></a>
    <?php endforeach; ?>
  </nav>
</section>

<section class="wrap sec-b sv-menu" id="packs" aria-label="Packs y precios">
  <nav class="sv-idx hide-m" aria-label="Packs" data-spy>
    <p class="lbl c-topo">Catálogo 2026</p>
    <?php foreach ($cards as $code => $c): ?>
    <a href="#pk-<?= e($code) ?>"><span class="sv-idx-n">0<?= e(substr($code, -1)) ?></span><span><?= e($c['title']) ?></span><span class="sv-idx-p"><?= e(money($packs[$code]['price'], false)) ?></span></a>
    <?php endforeach; ?>
    <a class="btn sm sv-idx-cta" href="/reservar">Armar mi solicitud</a>
  </nav>
  <div class="sv-rows">
    <?php foreach ($cards as $code => $c): ?>
    <article class="sv-row" id="pk-<?= e($code) ?>">
      <div class="ph rv sv-row-ph"><?= img($c['photo'], $c['alt'], ['sizes' => '(max-width: 960px) 112px, 300px']) ?></div>
      <div class="sv-row-body">
        <div class="sv-row-head">
          <p class="d sv-row-n" aria-hidden="true">0<?= e(substr($code, -1)) ?></p>
          <div class="stack gap-8">
            <p class="lbl c-terracota"><?= e($c['label']) ?></p>
            <h2 class="d sv-pk-title"><?= e($c['title']) ?></h2>
          </div>
          <p class="sv-pk-price"><span class="d"><?= e(money($packs[$code]['price'], false)) ?></span><span class="sv-cop">COP</span></p>
        </div>
        <p class="sv-pk-note"><?= e($c['note']) ?></p>
        <ul class="sv-pills" aria-label="Incluye">
          <?php foreach ($c['pills'] as $pill): ?><li><?= $check ?><?= e($pill) ?></li><?php endforeach; ?>
        </ul>
        <details class="sv-more">
          <summary><span>Todo lo que incluye</span><?= icon('plus', 'pl') ?></summary>
          <ul>
            <?php foreach ($packs[$code]['includes'] as $item): ?><li><?= e($item) ?></li><?php endforeach; ?>
          </ul>
        </details>
        <div class="wrap-row sv-row-cta">
          <a class="btn" href="<?= e($c['href']) ?>"><?= e($c['cta']) ?> <?= icon('arrow', 'arr') ?></a>
          <a class="btn ghost" href="<?= e(wa_href('precios-' . $code, $c['wa'])) ?>" rel="nofollow"><?= icon('whatsapp') ?> Preguntar</a>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
    <p class="rv sv-policy">Agenda con mínimo 2 días de anticipación. La fecha se separa con un abono del 50 % por Nequi o Bancolombia (te enviamos los datos por WhatsApp); si no puedes asistir, reagendamos. Avísanos cualquier cambio con 1 día de anticipación.</p>
  </div>
</section>

<section class="wrap sec-b">
  <div class="rv stack gap-18 sv-cmp-head">
    <p class="lbl c-terracota">Comparar</p>
    <h2 class="d d-m">Qué trae cada pack.</h2>
  </div>
  <div class="rv tbl-wrap" role="region" aria-label="Comparación de packs" tabindex="0">
    <table class="tbl sv-tbl">
      <thead>
        <tr>
          <th scope="col"><span class="sr-only">Incluye</span></th>
          <th scope="col"><span class="d">Pack 1</span></th>
          <th scope="col"><span class="d">Pack 2</span></th>
          <th scope="col"><span class="d">Pack 3</span></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($rows as [$label, $has]): ?>
        <tr>
          <th scope="row"><?= e($label) ?></th>
          <?php foreach ($has as $yes): ?>
          <td<?= $yes ? '' : ' class="no"' ?>><?php if ($yes): ?><?= $check ?><span class="sr-only">Sí</span><?php else: ?><span aria-hidden="true">—</span><span class="sr-only">No</span><?php endif; ?></td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
        <tr>
          <th scope="row">Precio <span class="c-topo">(COP)</span></th>
          <?php foreach (['pack1', 'pack2', 'pack3'] as $code): ?>
          <td class="nowrap sv-tbl-p"><?= e(money($packs[$code]['price'], false)) ?></td>
          <?php endforeach; ?>
        </tr>
      </tbody>
    </table>
  </div>
</section>

<section class="bg-arena sec" id="grados">
  <div class="wrap g2">
    <div class="ph px rv ar-45 m-43 arch-soft op-top"><?= img('fotos/social-cumple-beso', 'Clienta con maquillaje social de ojos ahumados dorados soplando un beso junto a su torta', ['sizes' => '(max-width: 960px) 100vw, 620px', 'class' => 'fill']) ?></div>
    <div class="stack gap-32">
      <p class="lbl rv c-terracota">Temporada de grados</p>
      <h2 class="d d-l rv">Maquillaje<br>para grados.</h2>
      <p class="rv body-l mw-460">Un look que se vea bien con toga, con flash y en la foto familiar. Pack 1 desde <?= e(money($packs['pack1']['price'])) ?>. En noviembre y diciembre la agenda se llena rápido.</p>
      <div class="rv wrap-row"><a class="btn" href="<?= e(wa_href('servicios-grados', 'grados')) ?>" rel="nofollow">Reservar para mi grado</a></div>
    </div>
  </div>
</section>

<section class="wrap sec" id="domicilio">
  <div class="g2 sv-home">
    <div class="stack gap-32">
      <p class="lbl rv c-terracota">A domicilio</p>
      <h2 class="d d-l rv">Llevamos<br>la luz contigo.</h2>
      <p class="rv body-l mw-480">Silla profesional, luz de estudio y skin care en tu casa, tu hotel o el lugar del evento en Bucaramanga, Floridablanca, Piedecuesta y Girón.</p>
      <ul class="rv sv-zones" aria-label="Zonas a domicilio">
        <?php foreach (config('app.business.area_served') as $zone): ?>
        <li><?= e($zone) ?></li>
        <?php endforeach; ?>
      </ul>
      <div class="rv"><a class="btn" href="<?= e(wa_href('servicios-domicilio', 'domicilio')) ?>" rel="nofollow">Pedir maquillaje a domicilio</a></div>
    </div>
    <div class="ph px rv ar-34 m-43 op-left"><?= img('fotos/proceso-domicilio-h', 'Maquilladora aplicando rubor a una novia en bata en su casa, junto al vestido colgado', ['sizes' => '(max-width: 960px) 100vw, 560px', 'class' => 'fill']) ?></div>
  </div>
</section>

<section class="wrap sec-b">
  <div class="rv cta mesh-dark sv-cta">
    <div class="stack gap-20 sv-cta-txt">
      <p class="lbl c-salmon">¿Dudas entre dos packs?</p>
      <h2 class="d sv-cta-d">Te ayudamos a elegir.</h2>
    </div>
    <a class="btn salmon" href="<?= e(wa_href('servicios-cta')) ?>" rel="nofollow">Escribir por WhatsApp <?= icon('arrow', 'arr') ?></a>
  </div>
</section>
