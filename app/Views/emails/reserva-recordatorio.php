<?php /** @var array $b @var string $fecha @var string $hora @var string $lugar @var string $statusUrl */ ?>
<p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#A5462F">Recordatorio</p>
<h1 style="margin:0 0 16px;font-size:30px;line-height:1.15;font-weight:300;color:#171312">Mañana es tu cita</h1>
<p style="margin:0 0 20px">Hola, <?= e(explode(' ', (string) $b['client_name'])[0]) ?>. Todo listo para tu maquillaje del <?= e($fecha) ?> a las <?= e($hora) ?>.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #E6DDD6;font-size:14px;margin:0 0 20px">
<tr><td style="padding:10px 0;color:#6E625C">Código</td><td align="right" style="padding:10px 0"><?= e($b['code']) ?></td></tr>
<tr><td style="padding:10px 0;color:#6E625C;border-top:1px solid #E6DDD6">Servicio</td><td align="right" style="padding:10px 0;border-top:1px solid #E6DDD6"><?= e($b['service_name']) ?></td></tr>
<tr><td style="padding:10px 0;color:#6E625C;border-top:1px solid #E6DDD6">Fecha</td><td align="right" style="padding:10px 0;border-top:1px solid #E6DDD6"><?= e($fecha) ?></td></tr>
<tr><td style="padding:10px 0;color:#6E625C;border-top:1px solid #E6DDD6">Hora</td><td align="right" style="padding:10px 0;border-top:1px solid #E6DDD6"><?= e($hora) ?></td></tr>
<tr><td style="padding:10px 0;color:#6E625C;border-top:1px solid #E6DDD6">Lugar</td><td align="right" style="padding:10px 0;border-top:1px solid #E6DDD6"><?= e($lugar) ?></td></tr>
</table>
<?php if ($b['location'] === 'home'): ?>
<p style="margin:0 0 20px">Llegamos a <strong><?= e((string) $b['address']) ?></strong>. Ten listo un espacio con buena luz natural, una mesa y un enchufe cerca.</p>
<?php else: ?>
<p style="margin:0 0 20px">Te esperamos en el estudio: <strong><?= e(config('app.business.address')) ?>, <?= e(config('app.business.city')) ?></strong>. <a href="<?= e(config('app.business.maps_url')) ?>" style="color:#A5462F">Cómo llegar</a>.</p>
<?php endif; ?>
<p style="margin:0 0 20px">Para que el maquillaje dure: piel limpia e hidratada, sin maquillaje, y si es posible el cabello ya peinado o recogido.</p>
<p style="margin:0"><a href="<?= e(wa_direct('Hola, tengo una pregunta sobre mi cita ' . $b['code'])) ?>" style="display:inline-block;background:#171312;color:#FBF8F5;text-decoration:none;padding:14px 24px;border-radius:999px;font-size:14px">Escribir por WhatsApp</a></p>
