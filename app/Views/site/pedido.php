<?php
/**
 * Pedido en línea: paso a Wompi y estado. No confía en los parámetros de retorno de Wompi.
 * @var array $o @var list<array> $codes @var bool $pay @var bool $autopay @var bool $returned
 * @var array{action:string,fields:array<string,string>} $checkout
 */
$labels = ['pendiente' => 'Pago pendiente', 'aprobada' => 'Pago aprobado', 'rechazada' => 'Pago rechazado', 'anulada' => 'Pago anulado', 'reembolsada' => 'Reembolsado'];
$chip = ['pendiente' => 'solicitada', 'aprobada' => 'confirmada', 'rechazada' => 'cancelada', 'anulada' => 'cancelada', 'reembolsada' => 'vencida'];
?>
<section class="wrap rsv">
  <?= App\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
  <h1 class="rsv-head">
    <span class="lbl c-terracota in">Pedido <?= e($o['reference']) ?></span>
    <span class="d rsv-title in d1"><?= $o['status'] === 'aprobada' ? 'Gracias por tu compra.' : ($pay ? 'Último paso: el pago.' : 'Estamos verificando tu pago.') ?></span>
  </h1>

  <div class="rsv-layout mt-16">
    <div class="pane">
      <div class="<?= $o['status'] === 'aprobada' ? 'mesh' : 'bg-arena' ?> rsv-done" aria-live="polite" data-order-status="<?= e($o['status']) ?>">
        <span class="rsv-chip <?= e($chip[$o['status']] ?? 'solicitada') ?>"><?= e($labels[$o['status']] ?? $o['status']) ?></span>
        <?php if ($pay): ?>
          <h2 class="d">Paga con Wompi</h2>
          <p class="txt">Te llevamos a la pasarela segura de Wompi (Bancolombia) para pagar con PSE, Nequi o tarjeta. Nunca vemos ni guardamos los datos de tu tarjeta.</p>
          <form method="get" action="<?= e($checkout['action']) ?>" <?= $autopay ? 'data-autopay' : '' ?>>
            <?php foreach ($checkout['fields'] as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach; ?>
            <button class="btn" type="submit">Pagar <?= e(money((int) $o['total'])) ?> con Wompi <?= icon('arrow', 'arr') ?></button>
          </form>
        <?php elseif ($o['status'] === 'pendiente'): ?>
          <h2 class="d">Un momento…</h2>
          <p class="txt">Wompi nos confirma el pago directamente. En cuanto llegue la confirmación verás el cambio aquí y te escribiremos al correo. Puedes recargar esta página en un minuto.</p>
          <div class="wrap-row"><a class="btn ghost" href="<?= e('/tienda/pedido/' . $o['reference'] . '?t=' . $o['public_token']) ?>">Actualizar estado</a></div>
        <?php elseif ($o['status'] === 'aprobada'): ?>
          <h2 class="d"><?= e($o['product_name']) ?></h2>
          <?php if ($codes !== []): ?>
            <p class="txt">Tu bono está listo. Te lo enviamos al correo; también puedes copiarlo aquí:</p>
            <?php foreach ($codes as $c): ?><p class="d gift-code"><?= e($c['code']) ?></p><p class="txt">Para <?= e($c['recipient'] ?? '') ?> · canjeable por WhatsApp con este código.</p><?php endforeach; ?>
          <?php elseif ($o['product_category'] === 'course'): ?>
            <p class="txt">Tu cupo está separado. Te escribimos por WhatsApp para confirmar la fecha de inicio y la lista de materiales.</p>
          <?php else: ?>
            <p class="txt">Te escribimos por WhatsApp para coordinar la entrega o la recogida en el estudio.</p>
          <?php endif; ?>
          <div class="wrap-row"><a class="btn" href="<?= e(wa_direct('Hola, compré en la tienda: pedido ' . $o['reference'])) ?>" rel="nofollow">Escribir por WhatsApp</a></div>
        <?php else: ?>
          <h2 class="d">No se completó el pago</h2>
          <p class="txt">Wompi no aprobó la transacción y no se hizo ningún cobro. Puedes intentarlo de nuevo o escribirnos por WhatsApp.</p>
          <div class="wrap-row"><a class="btn" href="/tienda/<?= e($o['product_slug']) ?>">Intentar de nuevo</a></div>
        <?php endif; ?>
      </div>
    </div>
    <aside class="sum" aria-label="Resumen del pedido">
      <p class="lbl c-topo">Tu pedido</p>
      <p class="d sum-name"><?= e($o['product_name']) ?></p>
      <div class="row"><span>Referencia</span><span><?= e($o['reference']) ?></span></div>
      <?php if (!empty($o['gift_recipient'])): ?><div class="row"><span>Para</span><span><?= e($o['gift_recipient']) ?></span></div><?php endif; ?>
      <div class="row"><span>A nombre de</span><span><?= e($o['client_name']) ?></span></div>
      <hr class="hr">
      <div class="row total"><span>Total</span><span><?= e(money((int) $o['total'])) ?></span></div>
      <p class="sum-note">Pago procesado por Wompi. Compras en línea con derecho de retracto según la Ley 1480 de 2011; consulta los <a href="/terminos">términos</a>.</p>
    </aside>
  </div>
</section>
