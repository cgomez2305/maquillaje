<?php
/** Layout del panel sin barra lateral: acceso, 2FA, recuperación y errores. @var string $content */
$notice = App\Http\Session::flash('notice');
$title = $title ?? ($meta['title'] ?? 'Panel');
?><!doctype html>
<html lang="es-CO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?></title>
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="stylesheet" href="<?= e(asset('css/tokens.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/panel.css')) ?>">
</head>
<body class="panel bare">
<main class="auth" id="contenido">
  <div class="auth-card">
    <div class="side-brand"><img src="<?= e(img_src('marca/isotipo-negativo', 80)) ?>" alt="" width="32" height="32"><span class="d">Fletcher</span><span class="pill">Panel</span></div>
    <?php if ($notice): ?><div class="flash ok" role="status"><?= e($notice) ?></div><?php endif; ?>
    <?php if (isset($status, $text) && !isset($content_is_form)): ?>
    <p class="lbl">Error <?= e((string) $status) ?></p>
    <h1 class="d auth-title"><?= e($title) ?></h1>
    <p class="muted"><?= e($text) ?></p>
    <a class="btn" href="/panel">Volver al panel</a>
    <?php else: ?>
    <?= $content ?>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
