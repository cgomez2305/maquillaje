<?php /** @var string $periodName @var int $total @var int $base @var int $lines @var string $url */ ?>
<p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#A5462F">Liquidación de comisiones</p>
<h1 style="margin:0 0 16px;font-size:26px;font-weight:300;color:#171312">El corte de <?= e($periodName) ?> está listo para aprobar</h1>
<p style="margin:0 0 16px"><?= e((string) $lines) ?> líneas · base <?= e(money($base)) ?> · comisión <strong><?= e(money($total)) ?></strong>.</p>
<p style="margin:0 0 20px">Revísalo en el panel y apruébalo. El corte se cierra cuando ambas partes lo aprueban y desde entonces no se puede modificar.</p>
<p style="margin:0"><a href="<?= e($url) ?>" style="display:inline-block;background:#171312;color:#FBF8F5;text-decoration:none;padding:14px 24px;border-radius:999px;font-size:14px">Revisar el corte</a></p>
