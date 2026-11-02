<?php /** @var array $b @var string $fecha @var string $hora @var string $lugar @var string $statusUrl */ ?>
<p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#A5462F">Fecha confirmada</p>
<h1 style="margin:0 0 16px;font-size:30px;line-height:1.15;font-weight:300;color:#171312">Te esperamos el<br><?= e($fecha) ?></h1>
<p style="margin:0 0 20px">Hola, <?= e(explode(' ', (string) $b['client_name'])[0]) ?>. Recibimos tu abono de <strong><?= e(money((int) $b['deposit_amount'])) ?></strong> y tu fecha quedó confirmada. El saldo de <?= e(money((int) $b['total'] - (int) $b['deposit_amount'])) ?> se paga el día del servicio.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #E6DDD6;font-size:14px;margin:0 0 20px">
<tr><td style="padding:10px 0;color:#6E625C">Código</td><td align="right" style="padding:10px 0"><?= e($b['code']) ?></td></tr>
<tr><td style="padding:10px 0;color:#6E625C;border-top:1px solid #E6DDD6">Servicio</td><td align="right" style="padding:10px 0;border-top:1px solid #E6DDD6"><?= e($b['service_name']) ?></td></tr>
<tr><td style="padding:10px 0;color:#6E625C;border-top:1px solid #E6DDD6">Fecha</td><td align="right" style="padding:10px 0;border-top:1px solid #E6DDD6"><?= e($fecha) ?></td></tr>
<tr><td style="padding:10px 0;color:#6E625C;border-top:1px solid #E6DDD6">Hora</td><td align="right" style="padding:10px 0;border-top:1px solid #E6DDD6"><?= e($hora) ?></td></tr>
<tr><td style="padding:10px 0;color:#6E625C;border-top:1px solid #E6DDD6">Lugar</td><td align="right" style="padding:10px 0;border-top:1px solid #E6DDD6"><?= e($lugar) ?></td></tr>
</table>
<p style="margin:0 0 20px">Un día antes te enviaremos un recordatorio. Llega con la piel limpia e hidratada, sin maquillaje.</p>
<p style="margin:0 0 20px"><a href="<?= e($statusUrl) ?>" style="display:inline-block;background:#171312;color:#FBF8F5;text-decoration:none;padding:14px 24px;border-radius:999px;font-size:14px">Ver mi reserva</a></p>
<p style="margin:0;font-size:13px;color:#6E625C">Política de cambios: el abono no es reembolsable. Si no puedes asistir, reprogramamos tu cita avisando con mínimo un día de anticipación; si el aviso llega después, aplica la penalización de los <a href="<?= e(url('/terminos')) ?>" style="color:#A5462F">términos</a>.</p>
