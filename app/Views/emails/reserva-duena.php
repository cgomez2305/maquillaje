<?php /** @var array $b @var string $fecha @var string $hora @var string $lugar @var string $panelUrl */ ?>
<p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#A5462F">Nueva reserva desde la web</p>
<h1 style="margin:0 0 16px;font-size:28px;font-weight:300;color:#171312"><?= e($b['code']) ?> · <?= e($b['client_name']) ?></h1>
<p style="margin:0 0 4px"><strong><?= e($b['service_name']) ?></strong> · <?= e((string) $b['people']) ?> persona(s)</p>
<p style="margin:0 0 4px"><?= e($fecha) ?> · <?= e($hora) ?></p>
<p style="margin:0 0 4px"><?= e($lugar) ?></p>
<p style="margin:0 0 4px">WhatsApp: <?= e(phone_display((string) $b['phone_e164'])) ?> · <?= e($b['client_email']) ?></p>
<p style="margin:0 0 4px">Evento: <?= e(config('app.booking.event_types')[$b['event_type']] ?? $b['event_type']) ?></p>
<?php if (!empty($b['notes'])): ?><p style="margin:12px 0 4px">Nota: <?= e($b['notes']) ?></p><?php endif; ?>
<p style="margin:12px 0 20px">Total <?= e(money((int) $b['total'])) ?> · abono <?= e(money((int) $b['deposit_amount'])) ?>. El cupo queda retenido 48 h.</p>
<p style="margin:0"><a href="<?= e($panelUrl) ?>" style="display:inline-block;background:#171312;color:#FBF8F5;text-decoration:none;padding:14px 24px;border-radius:999px;font-size:14px">Abrir en el panel</a></p>
