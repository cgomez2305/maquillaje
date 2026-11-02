<?php /** @var array $o @var list<array> $codes @var string $statusUrl */ ?>
<p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#A5462F">Pago aprobado</p>
<h1 style="margin:0 0 16px;font-size:28px;line-height:1.15;font-weight:300;color:#171312"><?= e($o['product_name']) ?></h1>
<p style="margin:0 0 16px">Hola, <?= e(explode(' ', (string) $o['client_name'])[0]) ?>. Wompi confirmó tu pago de <strong><?= e(money((int) $o['total'])) ?></strong>. Referencia <?= e($o['reference']) ?>.</p>
<?php if ($codes): ?>
<?php foreach ($codes as $c): ?>
<div style="background:#F98F7D;border-radius:20px;padding:28px 24px;margin:0 0 16px;color:#171312">
<p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase">Bono de regalo · Make Up by Fletcher</p>
<p style="margin:0 0 10px;font-size:30px;font-weight:300;letter-spacing:1px"><?= e($c['code']) ?></p>
<p style="margin:0">Para <?= e($c['recipient'] ?? '') ?> · <?= e($o['product_name']) ?> · valor <?= e(money((int) $c['value'])) ?></p>
</div>
<?php endforeach; ?>
<p style="margin:0 0 16px">Para usarlo, quien lo recibe escribe por WhatsApp con el código y agendamos su fecha. Vigencia: [Vigencia pendiente].</p>
<?php elseif ($o['product_category'] === 'course'): ?>
<p style="margin:0 0 16px">Tu cupo en el curso está separado. Te escribimos por WhatsApp para confirmar la fecha de inicio y enviarte la lista de materiales con descuento en la tienda aliada.</p>
<?php else: ?>
<p style="margin:0 0 16px">Te escribimos por WhatsApp para coordinar el envío o la recogida en el estudio.</p>
<?php endif; ?>
<p style="margin:0"><a href="<?= e($statusUrl) ?>" style="color:#A5462F">Ver tu pedido</a></p>
