<?php
/**
 * Aviso a la dueña: nueva cotización desde /estudio-maquillaje-floridablanca.
 * @var string $code @var string $name @var string $phone @var string $service @var string $place
 * @var string|null $when @var string $message
 */
$rows = [
    ['Código', $code],
    ['Nombre', $name],
    ['WhatsApp', phone_display($phone)],
    ['Servicio', $service],
    ['Fecha', $when ?? 'Sin fecha'],
    ['Lugar', $place],
];
?>
<p style="margin:0 0 6px;font-size:12px;letter-spacing:.2em;text-transform:uppercase;color:#A5462F">Nueva cotización</p>
<h1 style="margin:0 0 20px;font-size:26px;font-weight:400;color:#171312"><?= e($name) ?> quiere cotizar</h1>
<p style="margin:0 0 20px">La clienta llenó el formulario del estudio y se le abrió WhatsApp con el mensaje y el código <strong><?= e($code) ?></strong>. Si escribe, registra la reserva en el panel con ese código para que quede atribuida a la web.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px">
<?php foreach ($rows as [$k, $v]): ?>
<tr><td style="padding:10px 12px 10px 0;border-bottom:1px solid #E9DFD7;color:#6E625C;width:120px"><?= e($k) ?></td><td style="padding:10px 0;border-bottom:1px solid #E9DFD7;color:#171312"><?= e($v) ?></td></tr>
<?php endforeach; ?>
</table>
<?php if ($message !== ''): ?>
<p style="margin:20px 0 6px;color:#6E625C">Mensaje</p>
<p style="margin:0;white-space:pre-line;color:#171312"><?= e($message) ?></p>
<?php endif; ?>
<p style="margin:28px 0 0"><a href="<?= e('https://wa.me/' . ltrim($phone, '+')) ?>" style="display:inline-block;background:#171312;color:#FBF8F5;text-decoration:none;padding:14px 24px;border-radius:999px">Escribirle por WhatsApp</a></p>
