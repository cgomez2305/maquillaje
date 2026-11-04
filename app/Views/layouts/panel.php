<?php
/**
 * Layout del panel privado (Panel-*.dc.html).
 * @var string $content
 * @var string $title     título de la pestaña
 * @var string $active    clave de la sección activa
 * @var list<string>|null $js
 */
$u = App\Panel\Auth::user();
$role = $u['role'] ?? '';
$icons = [
    'resumen' => '<path d="M4 13h6V4H4zM14 20h6v-9h-6zM4 20h6v-3H4zM14 4v3h6V4z"/>',
    'reservas' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4"/>',
    'calendario' => '<rect x="3" y="5" width="18" height="16" rx="3"/><path d="M3 10h18M8 3v4M16 3v4M8 14h2M14 14h2M8 17h2"/>',
    'comisiones' => '<path d="M5 19L19 5M7 9a2 2 0 1 0 0-4 2 2 0 0 0 0 4zM17 19a2 2 0 1 0 0-4 2 2 0 0 0 0 4z"/>',
    'clientas' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18.5 20a6.5 6.5 0 0 0-3-5.5"/>',
    'ventas' => '<path d="M4 7h16l-1.5 12h-13zM9 7a3 3 0 0 1 6 0"/>',
    'historial' => '<path d="M12 8v5l3 2"/><circle cx="12" cy="12" r="9"/>',
    'diario' => '<path d="M5 4h11l3 3v13H5z"/><path d="M9 10h6M9 14h6M9 18h3"/>',
    'ajustes' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
];
$nav = [
    ['resumen', '/panel', 'Resumen', ['owner', 'partner']],
    ['reservas', '/panel/reservas', 'Reservas', ['owner', 'partner', 'staff']],
    ['calendario', '/panel/calendario', 'Calendario', ['owner', 'staff']],
    ['comisiones', '/panel/comisiones', 'Comisiones', ['owner', 'partner']],
    ['clientas', '/panel/clientas', 'Clientas', ['owner', 'partner']],
    ['ventas', '/panel/ventas', 'Ventas en línea', ['owner', 'partner']],
    ['historial', '/panel/historial', 'Historial', ['owner', 'partner']],
    ['diario', '/panel/diario', 'Diario', ['owner']],
    ['ajustes', '/panel/ajustes', 'Ajustes', ['owner']],
];
$notice = App\Http\Session::flash('notice');
$error = App\Http\Session::flash('error');
?><!doctype html>
<html lang="es-CO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title ?? 'Panel') ?> · Panel Fletcher</title>
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="preload" href="/assets/fonts/comfortaa-var.woff2" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/tokens.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/panel.css')) ?>">
<?php foreach (['panel-reservas.css', 'panel-comisiones.css', 'panel-extra.css'] as $sheet): ?>
<link rel="stylesheet" href="<?= e(asset('css/' . $sheet)) ?>">
<?php endforeach; ?>
</head>
<body class="panel">
<a class="skip" href="#contenido">Saltar al contenido</a>
<div class="app">
  <aside class="side">
    <div class="side-brand"><img src="<?= e(img_src('marca/isotipo-negativo', 80)) ?>" alt="" width="32" height="32"><span class="d">Fletcher</span><span class="pill">Panel</span></div>
    <nav aria-label="Panel">
      <?php foreach ($nav as [$key, $href, $label, $roles]): if (!in_array($role, $roles, true)) { continue; } ?>
      <a href="<?= e($href) ?>"<?= ($active ?? '') === $key ? ' aria-current="page"' : '' ?>><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icons[$key] ?></svg><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="who">
      <p><?= e($u['name'] ?? '') ?></p>
      <p class="sub"><?= e(App\Panel\Auth::ROLE_LABELS[$role] ?? '') ?></p>
      <div class="who-actions">
        <a href="/panel/seguridad">Seguridad</a>
        <form method="post" action="/panel/salir"><?= csrf_field() ?><button type="submit">Salir</button></form>
      </div>
    </div>
  </aside>
  <main class="main" id="contenido">
    <?php if ($notice): ?><div class="flash ok" role="status"><?= e($notice) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="flash err" role="alert"><?= e($error) ?></div><?php endif; ?>
    <?= $content ?>
  </main>
</div>
<script type="module" src="<?= e(asset('js/panel.js')) ?>"></script>
<?php foreach ($js ?? [] as $script): ?>
<script type="module" src="<?= e(asset('js/' . $script)) ?>"></script>
<?php endforeach; ?>
</body>
</html>
