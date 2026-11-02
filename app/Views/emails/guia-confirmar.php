<?php /** @var string $name @var string $link */ ?>
<p style="margin:0 0 8px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#A5462F">Guía gratuita para novias</p>
<h1 style="margin:0 0 20px;font-size:26px;line-height:1.2;font-weight:400;color:#171312">Confirma tu correo<?= $name !== '' ? ', ' . e($name) : '' ?>.</h1>
<p style="margin:0 0 20px">Recibimos tu solicitud de la guía de belleza para novias. Para enviártela necesitamos confirmar que este correo es tuyo.</p>
<p style="margin:0 0 28px"><a href="<?= e($link) ?>" style="display:inline-block;background:#171312;color:#FBF8F5;text-decoration:none;padding:14px 26px;border-radius:999px;font-size:14px">Confirmar y recibir la guía</a></p>
<p style="margin:0 0 8px;font-size:13px;color:#6E625C">Si no pediste la guía, ignora este correo: no te escribiremos de nuevo.</p>
<p style="margin:0;font-size:12px;color:#6E625C;word-break:break-all">Si el botón no funciona, copia este enlace: <?= e($link) ?></p>
