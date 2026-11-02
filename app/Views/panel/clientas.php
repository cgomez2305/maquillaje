<?php
/** @var list<array> $rows @var string $q @var int $page @var int $pages @var int $total @var int $activeWeb */
use App\Controllers\Panel\ClientasController;
use App\Panel\Auth;
$isPartner = Auth::is('partner');
?>
<link rel="stylesheet" href="<?= e(asset('css/panel-extra.css')) ?>">
<div class="top">
  <div><p class="lbl">Base de clientas</p><h1 class="d">Clientas</h1></div>
  <form class="top-actions" method="get" action="/panel/clientas" role="search">
    <label class="search"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
      <span class="sr-only">Buscar clientas</span>
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="<?= $isPartner ? 'Buscar por nombre' : 'Buscar por nombre, teléfono o correo' ?>" data-search>
    </label>
  </form>
</div>

<div class="kpis kpis-3">
  <div class="card kpi"><p class="lbl">Clientas registradas</p><p class="v sm"><?= e(number_format($total, 0, ',', '.')) ?></p><p class="muted small"><?= $q !== '' ? 'Resultado de la búsqueda' : 'Total en la base' ?></p></div>
  <div class="card kpi"><p class="lbl">Con atribución web vigente</p><p class="v sm"><?= e(number_format($activeWeb, 0, ',', '.')) ?></p><p class="muted small">Ventana de <?= (int) config('app.attribution.window_months', 6) ?> meses desde su primer registro web</p></div>
  <div class="card kpi"><p class="lbl">Privacidad</p><p class="small mt-10"><?= $isPartner ? 'Ves teléfonos y correos enmascarados (Ley 1581).' : 'El socio ve teléfonos y correos enmascarados (Ley 1581).' ?></p></div>
</div>

<section class="card">
  <div class="tbl-wrap">
    <table class="tbl">
      <caption class="sr-only">Clientas</caption>
      <thead><tr><th scope="col">Nombre</th><th scope="col">WhatsApp</th><th scope="col">Correo</th><th scope="col">Primer origen</th><th scope="col">Atribuida a la web hasta</th><th scope="col" class="n">Reservas</th><th scope="col" class="n">Valor realizado</th></tr></thead>
      <tbody>
      <?php if ($rows === []): ?>
        <tr><td colspan="7" class="empty"><?= $q !== '' ? 'No hay clientas que coincidan con la búsqueda.' : 'Aún no hay clientas registradas.' ?></td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r):
          $until = $r['web_attributed_until'] ? local_time($r['web_attributed_until']) : null;
          $vigente = $r['web_attributed_until'] && $r['web_attributed_until'] >= now_utc(); ?>
        <tr>
          <td><a href="/panel/clientas/<?= (int) $r['id'] ?>"><?= e($r['name']) ?></a><?php if ($r['anonymized_at']): ?> <span class="pill">Anonimizada</span><?php endif; ?></td>
          <td class="nowrap"><?= e(Auth::phone($r['phone_e164'])) ?></td>
          <td><?= e(Auth::email($r['email'])) ?></td>
          <td><?= e(ClientasController::SOURCES[$r['first_source']] ?? $r['first_source']) ?></td>
          <td class="nowrap"><?php if ($until): ?><span class="st <?= $vigente ? 'con' : 'ven' ?>"><?= e(fecha_corta($until)) ?></span><?php else: ?><span class="muted">—</span><?php endif; ?></td>
          <td class="n"><?= (int) $r['bookings'] ?></td>
          <td class="n"><?= e(money((int) $r['done_value'], false)) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <nav class="pager" aria-label="Páginas">
    <?php if ($page > 1): ?><a class="btn ghost" href="/panel/clientas?<?= e(http_build_query(['q' => $q, 'p' => $page - 1])) ?>">Anterior</a><?php endif; ?>
    <span class="muted small">Página <?= $page ?> de <?= $pages ?></span>
    <?php if ($page < $pages): ?><a class="btn ghost" href="/panel/clientas?<?= e(http_build_query(['q' => $q, 'p' => $page + 1])) ?>">Siguiente</a><?php endif; ?>
  </nav>
  <?php endif; ?>
</section>
