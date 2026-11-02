<?php
/** Layout de correo: tablas y estilos en línea (los clientes de correo no aplican CSP). @var string $content @var string $subject */
$base = config('app.url');
?><!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title><?= e($subject) ?></title></head>
<body style="margin:0;padding:0;background:#F2EAE3;font-family:Arial,Helvetica,sans-serif;color:#171312">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F2EAE3;padding:32px 12px">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#FBF8F5;border-radius:24px;overflow:hidden">
<tr><td style="padding:32px 36px 8px">
<img src="<?= e($base) ?>/assets/img/marca/logo-salmon-336.png" width="120" alt="Make Up by Fletcher" style="display:block;border:0">
</td></tr>
<tr><td style="padding:16px 36px 36px;font-size:15px;line-height:1.65;color:#4F4540">
<?= $content ?>
</td></tr>
<tr><td style="padding:24px 36px;background:#171312;color:#BDB1A9;font-size:12px;line-height:1.6">
Make Up by Fletcher · Carrera 29 #158-08, Floridablanca, Santander<br>
WhatsApp +57 316 191 0936 · <a href="<?= e($base) ?>" style="color:#F98F7D">maquillajebucaramanga.com</a>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
