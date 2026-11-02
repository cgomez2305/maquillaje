<?php /** @var list<array> $alerts @var array<string,string> $types @var string $from @var string $to @var string $url */ ?>
<p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#A5462F">Resumen semanal</p>
<h1 style="margin:0 0 16px;font-size:26px;font-weight:300;color:#171312"><?= count($alerts) ?> alerta<?= count($alerts) === 1 ? '' : 's' ?> del <?= e($from) ?> al <?= e($to) ?></h1>
<?php if ($alerts === []): ?>
<p style="margin:0 0 20px">Sin alertas esta semana.</p>
<?php else: ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;margin:0 0 20px">
<?php foreach ($alerts as $a): ?>
<tr><td style="padding:12px 0;border-top:1px solid #E6DDD6"><strong style="color:#171312"><?= e($types[$a['type']] ?? $a['type']) ?></strong><br><?= e($a['message']) ?><br><span style="font-size:12px;color:#6E625C"><?= e(fecha_corta(local_time($a['created_at']))) ?><?= $a['code'] ? ' · ' . e($a['code']) : '' ?></span></td></tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
<p style="margin:0"><a href="<?= e($url) ?>" style="display:inline-block;background:#171312;color:#FBF8F5;text-decoration:none;padding:14px 24px;border-radius:999px;font-size:14px">Abrir el panel</a></p>
