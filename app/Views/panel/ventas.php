<?php
/**
 * @var list<array> $rows @var string $status @var string $month @var array $sum @var list<array> $gifts
 * @var bool $canRedeem @var list<string> $slots @var array{errors:array,old:array,error:?string} $redeem
 */
use App\Controllers\Panel\VentasController;
use App\Panel\Auth;
$err = static fn(string $f): string => isset($redeem['errors'][$f]) ? '<span class="err" id="re-' . e($f) . '">' . e($redeem['errors'][$f]) . '</span>' : '';
$aria = static fn(string $f): string => isset($redeem['errors'][$f]) ? ' aria-invalid="true" aria-describedby="re-' . e($f) . '"' : '';
$old = $redeem['old'];
$fmt = static fn(?string $utc): string => $utc ? fecha_corta(local_time($utc)) : '—';
$months = [];
$m = new DateTimeImmutable('first day of this month', new DateTimeZone((string) config('app.timezone')));
for ($i = 0; $i < 12; $i++) {
    $months[$m->format('Y-m')] = ucfirst(mes_nombre((int) $m->format('n'))) . ' ' . $m->format('Y');
    $m = $m->modify('-1 month');
}
$giftStatus = ['activo' => 'con', 'canjeado' => 'rea', 'anulado' => 'can'];
?>
<link rel="stylesheet" href="<?= e(asset('css/panel-extra.css')) ?>">
<div class="top">
  <div><p class="lbl">Pagos con Wompi</p><h1 class="d">Ventas en línea</h1></div>
  <form class="top-actions" method="get" action="/panel/ventas">
    <label class="sr-only" for="v-estado">Estado</label>
    <select id="v-estado" name="estado" class="select" data-autosubmit>
      <option value="">Todos los estados</option>
      <?php foreach (VentasController::STATUS as $k => $v): ?><option value="<?= e($k) ?>"<?= $status === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
    </select>
    <label class="sr-only" for="v-mes">Mes</label>
    <select id="v-mes" name="mes" class="select" data-autosubmit>
      <option value="">Últimos pedidos</option>
      <?php foreach ($months as $k => $v): ?><option value="<?= e($k) ?>"<?= $month === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
    </select>
    <noscript><button class="btn ghost" type="submit">Filtrar</button></noscript>
  </form>
</div>

<div class="kpis kpis-3">
  <div class="card kpi"><p class="lbl">Pedidos</p><p class="v sm"><?= (int) $sum['count'] ?></p><p class="muted small">Con los filtros actuales</p></div>
  <div class="card kpi"><p class="lbl">Pagado y aprobado</p><p class="v sm"><?= e(money($sum['paid'], false)) ?></p><p class="muted small">COP · confirmado por el webhook de Wompi</p></div>
  <div class="card kpi dark"><p class="lbl">Comisión de ventas</p><p class="v sm"><?= e(money($sum['commission'], false)) ?></p><p class="muted small">COP · incluye reembolsos en negativo</p></div>
</div>

<section class="card">
  <div class="card-head"><h2 class="d">Pedidos</h2><p class="muted small">La URL de retorno nunca confirma un pago: solo el webhook firmado.</p></div>
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th scope="col">Pedido</th><th scope="col">Clienta</th><th scope="col">Productos</th><th scope="col">Estado</th><th scope="col">Pago</th><th scope="col">Referencia Wompi</th><th scope="col" class="n">Valor</th><th scope="col" class="n">Tasa</th><th scope="col" class="n">Comisión</th></tr></thead>
      <tbody>
      <?php if ($rows === []): ?><tr><td colspan="9" class="empty">No hay pedidos con estos filtros.</td></tr><?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="nowrap"><?= e($r['reference']) ?></td>
          <td><a href="/panel/clientas/<?= (int) $r['client_id'] ?>"><?= e($r['client_name']) ?></a></td>
          <td><?= e($r['items'] ?? '—') ?></td>
          <td><span class="st <?= e(VentasController::CHIP[$r['status']] ?? 'sol') ?>"><?= e(VentasController::STATUS[$r['status']] ?? $r['status']) ?></span></td>
          <td class="nowrap"><?= e($fmt($r['paid_at'])) ?></td>
          <td class="mono"><?= e($r['wompi_transaction_id'] ?? '—') ?></td>
          <td class="n"><?= e(money((int) $r['total'], false)) ?></td>
          <td class="n"><?= e(App\Domain\Commission\RateService::percent((int) $r['commission_rate_bp'])) ?></td>
          <td class="n"><?= e(money((int) $r['commission'], false)) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<div class="split">
  <section class="card">
    <div class="card-head"><h2 class="d">Bonos de regalo</h2><p class="muted small">Últimos 30</p></div>
    <div class="tbl-wrap">
      <table class="tbl">
        <thead><tr><th scope="col">Código</th><th scope="col">Servicio</th><th scope="col">Para</th><th scope="col">Estado</th><th scope="col">Reserva</th><th scope="col" class="n">Valor</th></tr></thead>
        <tbody>
        <?php if ($gifts === []): ?><tr><td colspan="6" class="empty">Aún no se han vendido bonos.</td></tr><?php endif; ?>
        <?php foreach ($gifts as $g): ?>
          <tr>
            <td class="mono nowrap"><?= e($g['code']) ?></td>
            <td><?= e($g['service_code']) ?></td>
            <td><?= e($g['recipient'] ?? '—') ?></td>
            <td><span class="st <?= e($giftStatus[$g['status']] ?? 'sol') ?>"><?= e(ucfirst($g['status'])) ?></span></td>
            <td class="nowrap"><?= $g['booking_code'] ? '<a href="/panel/reservas/' . e($g['booking_code']) . '">' . e($g['booking_code']) . '</a>' : '—' ?></td>
            <td class="n"><?= e(money((int) $g['value'], false)) ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="card" id="canje" aria-labelledby="canje-t">
    <h2 class="d card-title" id="canje-t">Canjear un bono</h2>
    <?php if (!$canRedeem): ?>
      <p class="muted small">Solo la dueña canjea bonos. El canje crea una reserva confirmada que no vuelve a comisionar: la comisión ya se generó con el pago del bono.</p>
    <?php else: ?>
      <p class="muted small">Crea la reserva confirmada, sin abono pendiente. No vuelve a comisionar.</p>
      <?php if ($redeem['error']): ?><div class="flash err" role="alert"><?= e($redeem['error']) ?></div><?php endif; ?>
      <form method="post" action="/panel/ventas/canjear" class="fg" novalidate>
        <?= csrf_field() ?>
        <div class="f full"><label for="rd-code">Código del bono</label><input id="rd-code" name="code" placeholder="BONO-XXXX-XXXX" autocomplete="off" required value="<?= e($old['code'] ?? '') ?>"<?= $aria('code') ?>><?= $err('code') ?></div>
        <div class="f full"><label for="rd-name">Nombre de quien lo usa</label><input id="rd-name" name="name" required maxlength="120" value="<?= e($old['name'] ?? '') ?>"<?= $aria('name') ?>><?= $err('name') ?></div>
        <div class="f"><label for="rd-phone">WhatsApp</label><input id="rd-phone" name="phone" type="tel" required value="<?= e($old['phone'] ?? '') ?>"<?= $aria('phone') ?>><?= $err('phone') ?></div>
        <div class="f"><label for="rd-email">Correo (opcional)</label><input id="rd-email" name="email" type="email" value="<?= e($old['email'] ?? '') ?>"<?= $aria('email') ?>><?= $err('email') ?></div>
        <div class="f"><label for="rd-date">Fecha</label><input id="rd-date" name="date" type="date" value="<?= e($old['date'] ?? '') ?>"<?= $aria('date') ?>><?= $err('date') ?></div>
        <div class="f"><label for="rd-time">Hora</label><select id="rd-time" name="time"<?= $aria('time') ?>><option value="">Elige</option><?php foreach ($slots as $t): ?><option value="<?= e($t) ?>"<?= ($old['time'] ?? '') === $t ? ' selected' : '' ?>><?= e(hora(new DateTimeImmutable('2000-01-01 ' . $t))) ?></option><?php endforeach; ?></select><?= $err('time') ?></div>
        <div class="f"><label for="rd-loc">Lugar</label><select id="rd-loc" name="location"<?= $aria('location') ?>><option value="studio"<?= ($old['location'] ?? 'studio') === 'studio' ? ' selected' : '' ?>>Estudio</option><option value="home"<?= ($old['location'] ?? '') === 'home' ? ' selected' : '' ?>>A domicilio</option></select><?= $err('location') ?></div>
        <div class="f"><label for="rd-addr">Dirección (domicilio)</label><input id="rd-addr" name="address" maxlength="255" value="<?= e($old['address'] ?? '') ?>"<?= $aria('address') ?>><?= $err('address') ?></div>
        <div class="f full"><label for="rd-notes">Nota</label><input id="rd-notes" name="notes" maxlength="500" value="<?= e($old['notes'] ?? '') ?>"></div>
        <p class="full hint small">Los bonos de curso solo se marcan como canjeados; la inscripción se coordina con la alumna.</p>
        <div class="full"><button class="btn" type="submit">Canjear y crear reserva</button></div>
      </form>
    <?php endif; ?>
  </section>
</div>
