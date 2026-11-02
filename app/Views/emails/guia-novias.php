<?php
/** @var string $name @var list<array<string,mixed>> $posts @var int $pack3 */
$base = config('app.url');
?>
<p style="margin:0 0 8px;font-size:12px;letter-spacing:2px;text-transform:uppercase;color:#A5462F">Tu guía para novias</p>
<h1 style="margin:0 0 20px;font-size:26px;line-height:1.2;font-weight:400;color:#171312">Tu checklist de belleza, mes a mes<?= $name !== '' ? ', ' . e($name) : '' ?>.</h1>
<p style="margin:0 0 16px">Gracias por confirmar tu correo. Aquí tienes lo esencial para llegar a tu boda con la piel lista y un maquillaje probado:</p>
<p style="margin:0 0 16px;padding:14px 16px;background:#F2EAE3;border-radius:12px">Checklist en PDF: <strong>[Guía PDF pendiente de diseño]</strong></p>
<?php if ($posts !== []): ?>
<p style="margin:0 0 8px;color:#171312"><strong>Lecturas recomendadas</strong></p>
<ul style="margin:0 0 20px;padding-left:18px">
<?php foreach ($posts as $p): ?>
  <li style="margin-bottom:6px"><a href="<?= e($base . '/diario/' . $p['slug']) ?>" style="color:#A5462F"><?= e($p['title']) ?></a></li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
<p style="margin:0 0 20px">Cuando tengas fecha, el <strong>Pack 3 · Novias y quinceañeras</strong> (<?= e(money($pack3)) ?>) incluye prueba previa con dos propuestas, skin care, maquillaje el día del evento, video reel y extra fijación.</p>
<p style="margin:0 0 24px"><a href="<?= e($base) ?>/maquillaje-de-novia-bucaramanga" style="display:inline-block;background:#171312;color:#FBF8F5;text-decoration:none;padding:14px 26px;border-radius:999px;font-size:14px">Conocer la experiencia novia</a></p>
<p style="margin:0;font-size:12px;color:#6E625C">Recibes este correo porque pediste la guía en maquillajebucaramanga.com. Para dejar de recibir novedades, responde a este correo con la palabra «baja».</p>
