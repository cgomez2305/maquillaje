<?php /** @var array $o @var list<array> $codes */ ?>
<p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#A5462F">Venta en línea aprobada</p>
<h1 style="margin:0 0 16px;font-size:26px;font-weight:300;color:#171312"><?= e($o['reference']) ?> · <?= e($o['product_name']) ?></h1>
<p style="margin:0 0 4px"><?= e($o['client_name']) ?> · <?= e(phone_display((string) $o['phone_e164'])) ?> · <?= e($o['client_email']) ?></p>
<p style="margin:0 0 4px">Valor: <?= e(money((int) $o['total'])) ?> · transacción Wompi <?= e($o['wompi_transaction_id'] ?? '') ?></p>
<?php foreach ($codes as $c): ?><p style="margin:0 0 4px">Bono <?= e($c['code']) ?> para <?= e($c['recipient'] ?? '') ?></p><?php endforeach; ?>
<p style="margin:16px 0 0"><a href="<?= e(url('/panel/ventas')) ?>" style="color:#A5462F">Ver en el panel</a></p>
