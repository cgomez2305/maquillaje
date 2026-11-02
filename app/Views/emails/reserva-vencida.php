<?php /** @var array $b @var string $fecha @var string $hora */ ?>
<p style="margin:0 0 6px;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#A5462F">Cupo liberado</p>
<h1 style="margin:0 0 16px;font-size:28px;line-height:1.15;font-weight:300;color:#171312">No recibimos el abono de tu reserva <?= e($b['code']) ?></h1>
<p style="margin:0 0 20px">Hola, <?= e(explode(' ', (string) $b['client_name'])[0]) ?>. Pasaron 48 horas sin recibir el abono del 50 % para el <?= e($fecha) ?> a las <?= e($hora) ?>, así que liberamos el cupo para otra clienta.</p>
<p style="margin:0 0 20px">Si todavía quieres esa fecha, escríbenos: si sigue libre, la separamos de nuevo.</p>
<p style="margin:0 0 12px"><a href="<?= e(url('/reservar')) ?>" style="display:inline-block;background:#171312;color:#FBF8F5;text-decoration:none;padding:14px 24px;border-radius:999px;font-size:14px">Reservar de nuevo</a></p>
<p style="margin:0"><a href="<?= e(wa_direct('Hola, mi reserva ' . $b['code'] . ' venció y quiero volver a agendar')) ?>" style="color:#A5462F">Escribir por WhatsApp</a></p>
