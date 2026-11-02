<?php
/**
 * Paso 4 «Listo» y estado de la reserva para la clienta (/reserva/{código}?t=…).
 * No muestra datos bancarios (§1.3): esos van solo en el correo.
 * @var array $b @var bool $isNew @var string $fecha @var string $hora @var string $statusLabel @var string $waUrl
 */
$labels = ['Servicio', 'Fecha', 'Datos', 'Listo'];
$names = ['pack1' => 'Maquillaje social', 'pack2' => 'Full experience', 'pack3' => 'Novias y quinceañeras'];
$pending = $b['status'] === 'solicitada';
?>
<section class="wrap rsv">
  <h1 class="rsv-head">
    <span class="lbl c-terracota in">Reservar cita · Make Up by Fletcher</span>
    <span class="d rsv-title in d1"><?= $isNew ? 'Separa tu fecha.' : 'Tu reserva.' ?></span>
  </h1>
  <p class="rsv-intro in d2"><?= $isNew ? 'Tres pasos y te enviamos el código de tu reserva. La fecha queda confirmada cuando recibimos el abono del 50 %.' : 'Aquí ves el estado de tu reserva. Guarda este enlace: es personal.' ?></p>

  <div class="rsv-layout">
    <div>
      <?php if ($isNew): ?>
      <ol class="rsv-steps" aria-label="Progreso">
        <?php foreach ($labels as $i => $label): ?>
        <li class="rsv-st on"<?= $i === 3 ? ' aria-current="step"' : '' ?>><span class="bar" aria-hidden="true"><span></span></span><span class="t">0<?= $i + 1 ?> · <?= e($label) ?></span></li>
        <?php endforeach; ?>
      </ol>
      <?php endif; ?>
      <section class="pane" aria-labelledby="t4">
        <div class="mesh rsv-done">
          <p class="lbl"><?= $isNew ? 'Reserva recibida' : 'Estado' ?></p>
          <?php if (!$isNew): ?><span class="rsv-chip <?= e($b['status']) ?>"><?= e($statusLabel) ?></span><?php endif; ?>
          <h2 id="t4" class="d">Tu código es<br><?= e($b['code']) ?></h2>
          <?php if ($pending): ?>
          <p class="txt">Te enviamos el resumen y los datos de pago a tu correo. Para confirmar la fecha, envía el comprobante del abono del 50 % por WhatsApp con este código.</p>
          <div class="wrap-row"><a class="btn" href="<?= e($waUrl) ?>" rel="nofollow">Enviar comprobante por WhatsApp</a><a class="btn ghost" href="/">Volver al inicio</a></div>
          <?php elseif ($b['status'] === 'confirmada' || $b['status'] === 'reprogramada'): ?>
          <p class="txt">Tu fecha está confirmada. Un día antes te llega el recordatorio por correo. Si necesitas un cambio, avísanos con mínimo un día de anticipación.</p>
          <div class="wrap-row"><a class="btn" href="<?= e(wa_direct('Hola, tengo una pregunta sobre mi reserva ' . $b['code'])) ?>" rel="nofollow">Escribir por WhatsApp</a></div>
          <?php elseif ($b['status'] === 'vencida'): ?>
          <p class="txt">No recibimos el abono en 48 horas y el cupo se liberó. Escríbenos si quieres volver a agendar.</p>
          <div class="wrap-row"><a class="btn" href="/reservar">Reservar de nuevo</a></div>
          <?php else: ?>
          <p class="txt">Gracias por confiar en nosotras. Si tienes cualquier duda sobre esta reserva, escríbenos por WhatsApp con tu código.</p>
          <?php endif; ?>
        </div>
        <?php if ($pending): ?>
        <div class="rsv-next">
          <div><p class="lbl c-terracota">01</p><p>Recibes el correo con tu código y los datos de pago.</p></div>
          <div><p class="lbl c-terracota">02</p><p>Envías el comprobante y confirmamos tu fecha.</p></div>
          <div><p class="lbl c-terracota">03</p><p>Un día antes te llega el recordatorio.</p></div>
        </div>
        <?php endif; ?>
      </section>
    </div>

    <aside class="sum" aria-label="Resumen de tu reserva">
      <p class="lbl c-topo">Tu reserva</p>
      <p class="d sum-name"><?= e($names[$b['service_code']] ?? $b['service_name']) ?></p>
      <div class="row"><span>Personas</span><span><?= (int) $b['people'] >= 4 ? '4 o más' : e((string) $b['people']) ?></span></div>
      <div class="row"><span>Fecha</span><span><?= e($fecha) ?></span></div>
      <div class="row"><span>Hora</span><span><?= e($hora) ?></span></div>
      <div class="row"><span>Lugar</span><span><?= $b['location'] === 'home' ? 'A domicilio' : 'Estudio, Floridablanca' ?></span></div>
      <hr class="hr">
      <div class="row total"><span>Total</span><span><?= e(money((int) $b['total'])) ?></span></div>
      <div class="row"><span>Abono para separar (50 %)</span><span><?= e(money((int) $b['deposit_amount'])) ?></span></div>
      <p class="sum-note">El abono no es reembolsable, pero puedes reprogramar avisando con un día de anticipación.</p>
    </aside>
  </div>
</section>
