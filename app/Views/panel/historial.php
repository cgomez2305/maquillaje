<?php
/**
 * Historial y verificación de integridad.
 * @var string $tab @var list<array> $rows @var list<string> $types @var string $type
 * @var ?string $from @var ?string $to @var int $page @var bool $hasMore @var ?array $verify
 */
use App\Domain\Booking\BookingStateMachine;

$evLabels = [
    'creada' => 'Reserva creada', 'abono_confirmado' => 'Abono confirmado', 'reprogramada' => 'Reprogramada', 'realizada' => 'Realizada',
    'cancelada' => 'Cancelada', 'valor_modificado' => 'Valor modificado', 'correo_enviado' => 'Correo enviado',
    'encuesta_respondida' => 'Encuesta respondida', 'estado' => 'Cambio de estado', 'comprobante' => 'Comprobante adjunto',
];
$label = static fn(string $t): string => $evLabels[$t] ?? ucfirst(str_replace('_', ' ', $t));
$st = static fn(?string $s): string => $s === null ? '' : (BookingStateMachine::LABELS[$s] ?? $s);
$qs = static function (array $over) use ($tab, $type, $from, $to): string {
    $q = array_filter(['vista' => $tab === 'panel' ? 'panel' : null, 'tipo' => $type ?: null, 'desde' => $from, 'hasta' => $to] + [], static fn($v) => $v !== null);
    return '/panel/historial?' . http_build_query(array_merge($q, $over));
};
$when = static function (string $utc): string {
    $t = local_time($utc);
    return $t ? fecha_corta($t) . ' · ' . $t->format('G:i') : '';
};
?>
<div class="top">
  <div class="stack gap-8"><p class="lbl">Registro inmutable</p><h1 class="d">Historial</h1></div>
  <div class="top-actions"><a class="btn<?= $tab === 'verificar' ? ' ghost' : '' ?>" href="/panel/historial/verificar">Verificar cadena</a></div>
</div>

<nav class="tabs" aria-label="Vista del historial">
  <a class="chip" href="/panel/historial"<?= $tab === 'reservas' ? ' aria-current="true"' : '' ?>>Reservas</a>
  <a class="chip" href="/panel/historial?vista=panel"<?= $tab === 'panel' ? ' aria-current="true"' : '' ?>>Panel</a>
  <a class="chip" href="/panel/historial/verificar"<?= $tab === 'verificar' ? ' aria-current="true"' : '' ?>>Verificación</a>
</nav>

<?php if ($verify !== null): $c = $verify['chain']; ?>
<section class="card stack" aria-labelledby="vt">
  <h2 id="vt" class="d card-title">Cadena de hashes de las reservas</h2>
  <div class="verify">
    <span class="ic-c <?= $c['ok'] ? 'yes' : 'no' ?>" aria-hidden="true"><?= $c['ok'] ? '✓' : '!' ?></span>
    <div>
      <?php if ($c['ok']): ?>
      <p><strong>Íntegra.</strong> Se recalcularon los <?= e(number_format($c['count'], 0, ',', '.')) ?> eventos y cada uno coincide con su hash y con el anterior. Nada se alteró ni se eliminó.</p>
      <?php else: ?>
      <p><strong>Se detectó una alteración.</strong> <?= e($c['reason']) ?></p>
      <?php if ($verify['broken']): ?><p class="small">Evento #<?= (int) $verify['broken']['id'] ?> · <?= e($verify['broken']['code']) ?> · <?= e($label($verify['broken']['type'])) ?> · <?= e($when($verify['broken']['created_at'])) ?></p><?php endif; ?>
      <p class="small muted">Revisa las copias de seguridad de la base de datos para restaurar el registro original.</p>
      <?php endif; ?>
    </div>
  </div>
  <p class="xs muted">Cada evento guarda el SHA-256 del evento anterior. Si alguien edita o borra un evento directamente en la base de datos, la cadena deja de coincidir a partir de ese punto.</p>
</section>
<section class="card stack" aria-labelledby="vp">
  <h2 id="vp" class="d card-title">Cortes cerrados</h2>
  <?php if (!$verify['periods']): ?><p class="small muted">Aún no hay cortes cerrados.</p><?php endif; ?>
  <?php foreach ($verify['periods'] as $vp): ?>
  <div class="row-line small"><span><?= e(ucfirst($vp['name'])) ?></span><span><?= $vp['ok'] ? '<span class="pill good">Coincide con la huella</span>' : '<span class="pill bad">No coincide</span>' ?></span></div>
  <?php endforeach; ?>
</section>
<?php else: ?>

<form class="filters" method="get" action="/panel/historial">
  <?php if ($tab === 'panel'): ?><input type="hidden" name="vista" value="panel"><?php endif; ?>
  <div class="f"><label for="ht">Tipo</label><select id="ht" name="tipo" data-autosubmit><option value="">Todos</option><?php foreach ($types as $t): ?><option value="<?= e($t) ?>"<?= $t === $type ? ' selected' : '' ?>><?= e($tab === 'panel' ? str_replace('_', ' ', $t) : $label($t)) ?></option><?php endforeach; ?></select></div>
  <div class="f"><label for="hd">Desde</label><input id="hd" type="date" name="desde" value="<?= e($from) ?>"></div>
  <div class="f"><label for="hh">Hasta</label><input id="hh" type="date" name="hasta" value="<?= e($to) ?>"></div>
  <button class="btn ghost" type="submit">Filtrar</button>
</form>

<section class="card" aria-label="<?= $tab === 'panel' ? 'Acciones del panel' : 'Eventos de reservas' ?>">
  <?php if (!$rows): ?><p class="empty">No hay registros con estos filtros.</p><?php else: ?>
  <div class="tbl-wrap" tabindex="0" role="region" aria-label="Tabla desplazable"><table class="tbl">
    <?php if ($tab === 'panel'): ?>
    <thead><tr><th scope="col">Fecha</th><th scope="col">Usuario</th><th scope="col">Acción</th><th scope="col">Objeto</th><th scope="col">IP</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td class="nowrap"><?= e($when($r['created_at'])) ?></td>
      <td><?= e($r['user_name'] ?? 'Sistema') ?></td>
      <td class="ev-type"><?= e(str_replace('_', ' ', $r['action'])) ?></td>
      <td class="muted"><?= e($r['target'] ?? '') ?></td>
      <td class="mono"><?= $r['ip'] !== null ? e((string) @inet_ntop($r['ip'])) : '—' ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <?php else: ?>
    <thead><tr><th scope="col">Fecha</th><th scope="col">Reserva</th><th scope="col">Evento</th><th scope="col">Estado</th><th scope="col">Motivo</th><th scope="col">Por</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
    <tr>
      <td class="nowrap"><?= e($when($r['created_at'])) ?></td>
      <td class="nowrap"><a href="/panel/reservas/<?= e($r['code']) ?>"><?= e($r['code']) ?></a></td>
      <td class="ev-type"><?= e($label($r['type'])) ?></td>
      <td class="nowrap small"><?= $r['from_status'] || $r['to_status'] ? e(trim($st($r['from_status']) . ' → ' . $st($r['to_status']), ' →')) : '' ?></td>
      <td class="wrap small"><?= e($r['reason'] ?? '') ?></td>
      <td><?= e($r['actor_name'] ?? 'Sistema') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <?php endif; ?>
  </table></div>
  <?php endif; ?>
  <div class="pager">
    <span><?= $page > 1 ? '<a href="' . e($qs(['p' => $page - 1])) . '">← Más recientes</a>' : '' ?></span>
    <span class="muted">Página <?= $page ?></span>
    <span><?= $hasMore ? '<a href="' . e($qs(['p' => $page + 1])) . '">Anteriores →</a>' : '' ?></span>
  </div>
</section>
<?php endif; ?>
