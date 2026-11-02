<?php /** @var array $b @var string $fecha @var string $hora @var string $lugar @var string $statusUrl */ ?>
<p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#A5462F">Nueva fecha</p>
<h1 style="margin:0 0 16px;font-size:30px;line-height:1.15;font-weight:300;color:#171312">Tu cita cambió de fecha</h1>
<p style="margin:0 0 20px">Hola, <?= e(explode(' ', (string) $b['client_name'])[0]) ?>. Reprogramamos tu reserva <strong><?= e($b['code']) ?></strong>. Tu abono sigue aplicado a la nueva fecha.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #E6DDD6;font-size:14px;margin:0 0 20px">
<tr><td style="padding:10px 0;color:#6E625C">Código</td><td align="right" style="padding:10px 0"><?= e($b['code']) ?></td></tr>
<tr><td style="padding:10px 0;color:#6E625C;border-top:1px solid #E6DDD6">Servicio</td><td align="right" style="padding:10px 0;border-top:1px solid #E6DDD6"><?= e($b['service_name']) ?></td></tr>
<tr><td style="padding:10px 0;color:#6E625C;border-top:1px solid #E6DDD6">Fecha</td><td align="right" style="padding:10px 0;border-top:1px solid #E6DDD6"><?= e($fecha) ?></td></tr>
<tr><td style="padding:10px 0;color:#6E625C;border-top:1px solid #E6DDD6">Hora</td><td align="right" style="padding:10px 0;border-top:1px solid #E6DDD6"><?= e($hora) ?></td></tr>
<tr><td style="padding:10px 0;color:#6E625C;border-top:1px solid #E6DDD6">Lugar</td><td align="right" style="padding:10px 0;border-top:1px solid #E6DDD6"><?= e($lugar) ?></td></tr>
</table>
<p style="margin:0"><a href="<?= e($statusUrl) ?>" style="display:inline-block;background:#171312;color:#FBF8F5;text-decoration:none;padding:14px 24px;border-radius:999px;font-size:14px">Ver mi reserva</a></p>
