<?php
/**
 * @var array $p @var bool $isNew @var array<string,string> $errors
 * @var list<string> $categories @var array<string,string> $links @var array<string,string> $covers
 */
$err = static fn(string $f): string => isset($errors[$f]) ? '<p class="err" id="e-' . e($f) . '">' . e($errors[$f]) . '</p>' : '';
$aria = static fn(string $f): string => isset($errors[$f]) ? ' aria-invalid="true" aria-describedby="e-' . e($f) . '"' : '';
?>
<div class="top">
  <div><p class="lbl"><a href="/panel/diario">Diario</a></p><h1 class="d"><?= $isNew ? 'Nuevo artículo' : 'Editar artículo' ?></h1></div>
  <?php if (!$isNew && ($p['status'] ?? '') === 'publicado'): ?><div class="top-actions"><a class="btn ghost" href="/diario/<?= e($p['slug']) ?>" target="_blank" rel="noopener">Ver publicado</a></div><?php endif; ?>
</div>
<?php if ($errors): ?><div class="flash err" role="alert">Revisa los campos marcados.</div><?php endif; ?>
<form method="post" class="split">
  <?= csrf_field() ?>
  <section class="card fg">
    <div class="f full"><label for="d-title">Título (H1)</label><input id="d-title" name="title" maxlength="200" required value="<?= e($p['title'] ?? '') ?>"<?= $aria('title') ?>><?= $err('title') ?></div>
    <div class="f"><label for="d-seo">Título SEO (≤ 60)</label><input id="d-seo" name="seo_title" maxlength="70" value="<?= e($p['seo_title'] ?? '') ?>"<?= $aria('seo_title') ?>><span class="hint">Si queda vacío se usa el título.</span><?= $err('seo_title') ?></div>
    <div class="f"><label for="d-slug">Dirección</label><input id="d-slug" name="slug" maxlength="160" value="<?= e($p['slug'] ?? '') ?>"<?= $aria('slug') ?>><span class="hint">/diario/…, en minúscula y con guiones.</span><?= $err('slug') ?></div>
    <div class="f full"><label for="d-meta">Meta descripción (50–155)</label><textarea id="d-meta" name="meta_description" maxlength="160" rows="2"<?= $aria('meta_description') ?>><?= e($p['meta_description'] ?? '') ?></textarea><?= $err('meta_description') ?></div>
    <div class="f"><label for="d-kw">Keyword principal</label><input id="d-kw" name="target_keyword" maxlength="120" value="<?= e($p['target_keyword'] ?? '') ?>"<?= $aria('target_keyword') ?>><?= $err('target_keyword') ?></div>
    <div class="f"><label for="d-kicker">Antetítulo</label><input id="d-kicker" name="kicker" maxlength="60" value="<?= e($p['kicker'] ?? '') ?>" placeholder="Novias · Precios 2026"></div>
    <div class="f full"><label for="d-ex">Extracto (tarjeta del listado)</label><textarea id="d-ex" name="excerpt" maxlength="300" rows="2"<?= $aria('excerpt') ?>><?= e($p['excerpt'] ?? '') ?></textarea><?= $err('excerpt') ?></div>
    <div class="f full"><label for="d-body">Cuerpo (HTML)</label><textarea id="d-body" name="body_html" rows="22" class="mono"<?= $aria('body_html') ?>><?= e($p['body_html'] ?? '') ?></textarea>
      <span class="hint">Etiquetas permitidas: p, h2, h3, ul, ol, li, strong, em, a, table, blockquote, figure, img (solo /assets). Lo demás se elimina al guardar. Precios: escribe {{precio:pack1}}, {{precio:pack2}}, {{precio:pack3}}, {{precio:curso-auto}} o {{precio:curso-pro}}.</span><?= $err('body_html') ?></div>
    <div class="f full"><label for="d-faq">Preguntas frecuentes</label><textarea id="d-faq" name="faq_text" rows="8"<?= $aria('faq_text') ?>><?= e($p['faq_text'] ?? '') ?></textarea>
      <span class="hint">Una por bloque: «P: pregunta», en la línea siguiente «R: respuesta», y una línea en blanco entre preguntas. Se marca como FAQPage para Google.</span><?= $err('faq_text') ?></div>
  </section>
  <aside class="card stack" aria-label="Publicación del artículo">
    <div class="f"><label for="d-status">Estado</label><select id="d-status" name="status"><option value="borrador"<?= ($p['status'] ?? '') !== 'publicado' ? ' selected' : '' ?>>Borrador</option><option value="publicado"<?= ($p['status'] ?? '') === 'publicado' ? ' selected' : '' ?>>Publicado</option></select></div>
    <div class="f"><label for="d-cat">Categoría</label><select id="d-cat" name="category"<?= $aria('category') ?>><?php foreach ($categories as $c): ?><option<?= ($p['category'] ?? '') === $c ? ' selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select><?= $err('category') ?></div>
    <div class="f"><label for="d-link">Enlaza a</label><select id="d-link" name="links_to"><option value="">—</option><?php foreach ($links as $href => $label): ?><option value="<?= e($href) ?>"<?= ($p['links_to'] ?? '') === $href ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
    <div class="f"><label for="d-cover">Portada</label><select id="d-cover" name="cover"><option value="">—</option><?php foreach ($covers as $k => $label): ?><option value="<?= e($k) ?>"<?= ($p['cover'] ?? '') === $k ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></div>
    <div class="f"><label for="d-min">Minutos de lectura</label><input id="d-min" name="reading_minutes" type="number" min="0" max="119" value="<?= e((string) ($p['reading_minutes'] ?? '')) ?>"><span class="hint">Vacío o 0: se calcula solo.</span></div>
    <label class="ckb"><input type="checkbox" name="featured" value="1"<?= !empty($p['featured']) ? ' checked' : '' ?>> Artículo destacado del Diario</label>
    <button class="btn block" type="submit">Guardar</button>
    <p class="xs muted">Al publicar, el artículo entra al sitemap automáticamente.</p>
  </aside>
</form>
