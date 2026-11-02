<?php /** @var list<array> $posts */ ?>
<div class="top">
  <div><p class="lbl">Blog educativo</p><h1 class="d">Diario</h1></div>
  <div class="top-actions"><a class="btn" href="/panel/diario/nuevo">Nuevo artículo</a></div>
</div>
<section class="card">
  <div class="tbl-wrap">
    <table class="tbl">
      <thead><tr><th>Título</th><th>Categoría</th><th>Estado</th><th>Publicado</th><th>Actualizado</th><th class="n"></th></tr></thead>
      <tbody>
        <?php foreach ($posts as $p): $pub = local_time($p['published_at']); $upd = local_time($p['updated_at']); ?>
        <tr>
          <td><a class="rowlink" href="/panel/diario/<?= (int) $p['id'] ?>"><?= e($p['title']) ?></a><?= $p['featured'] ? ' <span class="pill">Destacado</span>' : '' ?></td>
          <td><?= e($p['category']) ?></td>
          <td><span class="st <?= $p['status'] === 'publicado' ? 'con' : 'ven' ?>"><?= $p['status'] === 'publicado' ? 'Publicado' : 'Borrador' ?></span></td>
          <td class="nowrap"><?= $pub ? e(fecha_corta($pub)) : '—' ?></td>
          <td class="nowrap"><?= $upd ? e(fecha_corta($upd)) : '—' ?></td>
          <td class="n"><?php if ($p['status'] === 'publicado'): ?><a href="/diario/<?= e($p['slug']) ?>" target="_blank" rel="noopener">Ver</a><?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if ($posts === []): ?><tr><td colspan="6" class="empty">Todavía no hay artículos.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
