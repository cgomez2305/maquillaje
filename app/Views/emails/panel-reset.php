<?php /** @var string $name @var string $url */ ?>
<h1 style="margin:0 0 16px;font-size:26px;font-weight:300;color:#171312">Restablecer tu contraseña</h1>
<p style="margin:0 0 16px">Hola, <?= e($name) ?>. Recibimos una solicitud para cambiar la contraseña del panel. El enlace sirve una sola vez y caduca en 30 minutos.</p>
<p style="margin:0 0 20px"><a href="<?= e($url) ?>" style="display:inline-block;background:#171312;color:#FBF8F5;text-decoration:none;padding:14px 24px;border-radius:999px;font-size:14px">Crear contraseña nueva</a></p>
<p style="margin:0;font-size:13px;color:#6E625C">Si no fuiste tú, ignora este correo: tu contraseña no cambia.</p>
