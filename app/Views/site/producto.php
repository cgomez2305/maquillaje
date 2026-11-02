<?php
/**
 * Ficha de producto (derivada de Tienda.dc.html) con formulario de compra.
 * @var array<string,mixed> $product
 * @var bool $isGift
 * @var bool $isCourse
 * @var bool $purchasable
 * @var list<string> $includes
 * @var list<array<string,mixed>> $related
 * @var array<string,string> $errors
 * @var array<string,string> $old
 * @var string|null $notice
 * @var list<array{0:string,1:?string}> $crumbs
 */
$kind = $isGift ? 'Bono de regalo' : ($isCourse ? 'Curso en el estudio' : 'Kit Fletcher');
$title = $isGift ? preg_replace('/^Bono · /u', '', (string) $product['name']) : (string) $product['name'];
$action = '/tienda/' . $product['slug'] . '/comprar';
$val = static fn(string $k): string => (string) ($old[$k] ?? '');
$err = static fn(string $k): string => isset($errors[$k]) ? ' has-err' : '';
$desc = static fn(string $k): string => isset($errors[$k]) ? ' aria-invalid="true" aria-describedby="err-' . $k . '"' : '';
$errMsg = static fn(string $k): string => isset($errors[$k]) ? '<p class="err" id="err-' . e($k) . '">' . e($errors[$k]) . '</p>' : '';
$steps = $isGift
    ? [
        ['Pagas en línea', 'Con PSE, Nequi o tarjeta a través de Wompi, en una página segura.'],
        ['Recibes el bono', 'Te llega por correo con un código único BONO y el nombre de quien lo recibe.'],
        ['Agenda su fecha', 'Quien lo recibe escribe por WhatsApp con el código y elige día y hora.'],
        ['Vive la experiencia', 'En el estudio de Floridablanca o a domicilio, según el bono.'],
    ]
    : [
        ['Pagas en línea', 'Con PSE, Nequi o tarjeta a través de Wompi, en una página segura.'],
        ['Confirmamos tu cupo', 'Recibes un correo con la confirmación y la lista de materiales.'],
        ['Eliges tu grupo', 'Te escribimos por WhatsApp con las fechas del próximo grupo.'],
        ['Clases en el estudio', 'Carrera 29 #158-08, Floridablanca, con luz y silla profesional.'],
    ];
?>
<section class="wrap pdp">
  <?= App\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
  <div class="pdp-grid">
    <div class="pdp-media in d1">
      <?php if (!empty($product['image'])): ?>
      <div class="arch px"><?= img((string) $product['image'], $isGift ? 'Experiencia incluida en el ' . mb_strtolower((string) $product['name']) : (string) $product['name'] . ' en el estudio de Make Up by Fletcher', ['priority' => true, 'sizes' => '(max-width: 960px) 100vw, 560px']) ?></div>
      <?php else: ?>
      <div class="arch pdp-tile"><img src="<?= e(img_src('marca/isotipo-salmon', 240)) ?>" alt="" width="240" height="240"></div>
      <?php endif; ?>
      <?php if ($isGift): ?>
      <div class="pdp-card" aria-hidden="true">
        <img src="<?= e(img_src('marca/isotipo-nude', 360)) ?>" alt="" width="56" height="56">
        <div><p class="lbl">Bono de regalo</p><p class="pdp-card-code">BONO-••••-••••</p></div>
      </div>
      <?php endif; ?>
    </div>

    <div class="pdp-info">
      <h1 class="hero-title">
        <span class="lbl in c-terracota"><?= e($kind) ?></span>
        <span class="d pdp-h1 in d1"><?= e($title) ?></span>
      </h1>
      <p class="pdp-price in d2"><span class="d"><?= e(money($product['price'], false)) ?></span> <span class="pdp-cur">COP</span></p>
      <p class="lead in d2"><?= e($product['description'] ?? $product['summary']) ?></p>

      <?php if ($includes !== []): ?>
      <div class="in d3">
        <p class="lbl c-topo pdp-sub"><?= $isCourse ? 'Del curso' : 'Qué incluye' ?></p>
        <ul class="pdp-list">
          <?php foreach ($includes as $item): ?>
          <li><?= icon('check', 'ck') ?><span><?= e($item) ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if ($purchasable): ?>
      <form class="buy in d4" method="post" action="<?= e($action) ?>" novalidate>
        <?= csrf_field() ?>
        <?= spam_fields() ?>
        <div class="buy-head">
          <p class="lbl c-terracota"><?= $isGift ? 'Regalar este bono' : 'Inscribirme' ?></p>
          <p class="buy-total"><span>Total</span> <strong><?= e(money($product['price'])) ?></strong></p>
        </div>
        <?php if ($notice): ?><p class="notice err" role="alert"><?= e($notice) ?></p><?php endif; ?>
        <div class="fg">
          <div class="f<?= $err('name') ?>">
            <label for="buy-name"><?= $isGift ? 'Tu nombre' : 'Nombre completo' ?></label>
            <input id="buy-name" name="name" type="text" autocomplete="name" required maxlength="120" value="<?= e($val('name')) ?>"<?= $desc('name') ?>>
            <?= $errMsg('name') ?>
          </div>
          <div class="f<?= $err('phone') ?>">
            <label for="buy-phone">WhatsApp</label>
            <input id="buy-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" required maxlength="20" placeholder="300 123 4567" value="<?= e($val('phone')) ?>"<?= $desc('phone') ?>>
            <?= $errMsg('phone') ?>
          </div>
          <div class="f full<?= $err('email') ?>">
            <label for="buy-email">Correo</label>
            <input id="buy-email" name="email" type="email" autocomplete="email" required maxlength="190" placeholder="Aquí te llega la confirmación<?= $isGift ? ' y el bono' : '' ?>" value="<?= e($val('email')) ?>"<?= $desc('email') ?>>
            <?= $errMsg('email') ?>
          </div>
          <?php if ($isGift): ?>
          <div class="f full<?= $err('gift_recipient') ?>">
            <label for="buy-recipient">Nombre de quien lo recibe</label>
            <input id="buy-recipient" name="gift_recipient" type="text" required maxlength="120" placeholder="Así aparecerá en la tarjeta" value="<?= e($val('gift_recipient')) ?>"<?= $desc('gift_recipient') ?>>
            <?= $errMsg('gift_recipient') ?>
          </div>
          <div class="f full<?= $err('gift_message') ?>">
            <label for="buy-message">Mensaje en la tarjeta <span class="opt">(opcional)</span></label>
            <textarea id="buy-message" name="gift_message" rows="3" maxlength="300" placeholder="Para tu grado, con todo el cariño…"<?= $desc('gift_message') ?>><?= e($val('gift_message')) ?></textarea>
            <?= $errMsg('gift_message') ?>
          </div>
          <?php endif; ?>
        </div>
        <div class="buy-checks">
          <label class="ckb<?= $err('consent') ?>">
            <input type="checkbox" name="consent" value="1" required<?= $val('consent') === '1' ? ' checked' : '' ?><?= $desc('consent') ?>>
            <span>Autorizo el tratamiento de mis datos personales según la <a href="/politica-de-privacidad" target="_blank" rel="noopener">política de tratamiento de datos</a> (Ley 1581 de 2012).</span>
          </label>
          <?= $errMsg('consent') ?>
          <label class="ckb<?= $err('terms') ?>">
            <input type="checkbox" name="terms" value="1" required<?= $val('terms') === '1' ? ' checked' : '' ?><?= $desc('terms') ?>>
            <span>Acepto los <a href="/terminos" target="_blank" rel="noopener">términos de compra y reservas</a>.</span>
          </label>
          <?= $errMsg('terms') ?>
        </div>
        <button class="btn buy-btn" type="submit">Pagar con Wompi <?= icon('arrow', 'arr') ?></button>
        <p class="buy-safe"><?= icon('check', 'ck') ?><span>El pago se hace en Wompi (Bancolombia) con PSE, Nequi o tarjeta. Nunca vemos ni guardamos los datos de tu tarjeta. La confirmación llega a tu correo cuando Wompi aprueba el pago.</span></p>
      </form>
      <?php else: ?>
      <div class="buy in d4">
        <p class="notice">Este producto no está disponible para compra en línea en este momento.</p>
        <a class="btn" href="<?= e(wa_href('tienda-' . $product['slug'], 'tienda')) ?>" rel="nofollow">Pedir por WhatsApp <?= icon('arrow', 'arr') ?></a>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="bg-arena sec">
  <div class="wrap">
    <div class="rv sec-head">
      <div>
        <p class="lbl c-terracota">Cómo funciona</p>
        <h2 class="d d-l"><?= $isGift ? 'Del pago<br>a la silla.' : 'Del pago<br>a la primera clase.' ?></h2>
      </div>
      <a class="btn ghost" href="<?= e(wa_href('tienda-' . $product['slug'] . '-dudas', 'tienda')) ?>" rel="nofollow">Preguntar por WhatsApp</a>
    </div>
    <div class="steps rv">
      <?php foreach ($steps as $i => [$name, $text]): ?>
      <div><p class="lbl"><?= e(sprintf('%02d', $i + 1)) ?></p><p class="step-name"><?= e($name) ?></p><p class="step-text"><?= e($text) ?></p></div>
      <?php endforeach; ?>
    </div>
    <p class="rv pdp-fine"><?php if ($isGift): ?>Vigencia del bono: <span class="pending">[Vigencia pendiente]</span>. El bono se canjea una sola vez y no es reembolsable en dinero.<?php else: ?>Próximos grupos: <span class="pending">[Fechas y horarios de los próximos grupos]</span>.<?php endif; ?> Consulta los <a href="/terminos">términos</a>.</p>
  </div>
</section>

<?php if ($related !== []): ?>
<section class="wrap sec">
  <div class="rv sec-head">
    <div>
      <p class="lbl c-terracota"><?= $isGift ? 'Otros bonos' : ($isCourse ? 'Otros cursos' : 'También en la tienda') ?></p>
      <h2 class="d d-m"><?= $isGift ? 'También puedes<br>regalar.' : ($isCourse ? 'Sigue<br>aprendiendo.' : 'Más de<br>la tienda.') ?></h2>
    </div>
    <a class="btn ghost" href="/tienda">Ver toda la tienda</a>
  </div>
  <div class="g3 gifts">
    <?php foreach ($related as $i => $p): ?>
    <a class="card rv" href="/tienda/<?= e($p['slug']) ?>" data-delay="<?= e((string) ($i * 0.06)) ?>">
      <?php if (!empty($p['image'])): ?>
      <div class="ph ar-45"><?= img((string) $p['image'], (string) $p['name'], ['sizes' => '(max-width: 960px) 100vw, 420px']) ?></div>
      <?php endif; ?>
      <div class="between"><h3 class="d d-card"><?= e($p['name']) ?></h3><p class="price"><?= e(money($p['price'])) ?></p></div>
      <p><?= e($p['summary']) ?></p>
      <hr class="hr">
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
