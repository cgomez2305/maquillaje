<?php /** @var array $b @var string $fecha @var string $hora @var string $lugar @var string $statusUrl @var string $waUrl @var array $pay */ ?>
<p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#A5462F">Reserva recibida</p>
<h1 style="margin:0 0 16px;font-size:30px;line-height:1.15;font-weight:300;color:#171312">Tu código es<br><?= e($b['code']) ?></h1>
<p style="margin:0 0 20px">Hola, <?= e(explode(' ', (string) $b['client_name'])[0]) ?>. Recibimos tu solicitud. Tu fecha queda <strong>separada por 48 horas</strong>; se confirma cuando recibamos el abono del 50 %.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #E6DDD6;font-size:14px;margin:0 0 20px">
<tr><td style="padding:10px 0;color:#6E625C">Servicio</td><td align="right" style="padding:10px 0"><?= e($b['service_name']) ?></td></tr>
<tr><td style="padding:10px 0;color:#6E625C;border-top:1px solid #E6DDD6">Personas</td><td align="right" style="padding:10px 0;border-top:1px solid #E6DDD6"><?= e((string) $b['people']) ?></td></tr>
<tr><td style="padding:10px 0;color:#6E625C;border-top:1px solid #E6DDD6">Fecha</td><td align="right" style="padding:10px 0;border-top:1px solid #E6DDD6"><?= e($fecha) ?></td></tr>
<tr><td style="padding:10px 0;color:#6E625C;border-top:1px solid #E6DDD6">Hora</td><td align="right" style="padding:10px 0;border-top:1px solid #E6DDD6"><?= e($hora) ?></td></tr>
<tr><td style="padding:10px 0;color:#6E625C;border-top:1px solid #E6DDD6">Lugar</td><td align="right" style="padding:10px 0;border-top:1px solid #E6DDD6"><?= e($lugar) ?></td></tr>
<tr><td style="padding:12px 0;border-top:1px solid #E6DDD6;color:#171312"><strong>Total</strong></td><td align="right" style="padding:12px 0;border-top:1px solid #E6DDD6;color:#171312"><strong><?= e(money((int) $b['total'])) ?></strong></td></tr>
<tr><td style="padding:10px 0;color:#6E625C;border-top:1px solid #E6DDD6">Abono para separar (50 %)</td><td align="right" style="padding:10px 0;border-top:1px solid #E6DDD6"><?= e(money((int) $b['deposit_amount'])) ?></td></tr>
</table>
<div style="background:#F2EAE3;border-radius:16px;padding:18px 20px;margin:0 0 20px">
<p style="margin:0 0 8px;color:#171312"><strong>Datos para el abono</strong></p>
<p style="margin:0">Nequi: <?= e($pay['nequi']) ?><br>Bancolombia: <?= e($pay['bancolombia']) ?><br>Titular: <?= e($pay['holder']) ?></p>
</div>
<p style="margin:0 0 20px">Envía el comprobante por WhatsApp con tu código <strong><?= e($b['code']) ?></strong>. Te confirmamos la fecha en cuanto lo recibamos.</p>
<p style="margin:0 0 24px"><a href="<?= e($waUrl) ?>" style="display:inline-block;background:#171312;color:#FBF8F5;text-decoration:none;padding:14px 24px;border-radius:999px;font-size:14px">Enviar comprobante por WhatsApp</a></p>
<p style="margin:0 0 6px"><a href="<?= e($statusUrl) ?>" style="color:#A5462F">Ver el estado de tu reserva</a></p>
<p style="margin:16px 0 0;font-size:13px;color:#6E625C">El abono no es reembolsable; si no puedes asistir, reprogramamos tu cita. Avísanos cualquier cambio con mínimo un día de anticipación. Si no recibimos el abono en 48 horas, el cupo se libera.</p>
