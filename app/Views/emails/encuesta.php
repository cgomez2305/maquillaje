<?php /** @var array $b @var string $fecha @var string $surveyUrl */ ?>
<p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#A5462F">Tu opinión</p>
<h1 style="margin:0 0 16px;font-size:30px;line-height:1.15;font-weight:300;color:#171312">¿Cómo te fue con tu maquillaje?</h1>
<p style="margin:0 0 20px">Hola, <?= e(explode(' ', (string) $b['client_name'])[0]) ?>. Gracias por confiar en Make Up by Fletcher para tu cita del <?= e($fecha) ?>. Cuéntanos en menos de un minuto cómo te fue: nos ayuda a cuidar cada detalle.</p>
<p style="margin:0 0 20px"><a href="<?= e($surveyUrl) ?>" style="display:inline-block;background:#171312;color:#FBF8F5;text-decoration:none;padding:14px 24px;border-radius:999px;font-size:14px">Responder la encuesta</a></p>
<p style="margin:0;font-size:13px;color:#6E625C">Si te gustó el resultado, al final te dejamos el enlace para contarlo en Google.</p>
