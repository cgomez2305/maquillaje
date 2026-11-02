<?php
/**
 * @var array $user @var bool $enabled @var ?string $secret @var ?string $otpauth @var ?array $codes
 * @var int $remaining @var ?string $error @var bool $setupOnly
 */
?>
<?php if (!$setupOnly): ?>
<div class="top"><div><p class="lbl">Tu cuenta</p><h1 class="d">Seguridad</h1></div></div>
<?php else: ?>
<div><p class="lbl">Antes de entrar</p><h1 class="d auth-title">Activa la verificación en dos pasos</h1></div>
<p class="small muted">Es obligatoria para la dueña y el socio: protege el registro de reservas y comisiones.</p>
<?php endif; ?>
<?php if ($error): ?><div class="flash err" role="alert"><?= e($error) ?></div><?php endif; ?>

<?php if ($codes): ?>
<section class="card stack" aria-labelledby="bc">
  <h2 id="bc" class="d">Tus códigos de respaldo</h2>
  <p class="small">Guárdalos en un lugar seguro. Cada código sirve una sola vez si pierdes el teléfono. No se volverán a mostrar.</p>
  <div class="codes"><?php foreach ($codes as $c): ?><span><?= e($c) ?></span><?php endforeach; ?></div>
  <a class="btn" href="/panel">Ir al panel</a>
</section>
<?php endif; ?>

<?php if (!$enabled && $secret): ?>
<section class="card stack" aria-labelledby="t2fa">
  <h2 id="t2fa" class="d">Configurar la app</h2>
  <ol class="small">
    <li>Instala Google Authenticator, 1Password, Authy o similar.</li>
    <li>Escanea el código QR o escribe la clave manualmente.</li>
    <li>Escribe el código de 6 dígitos que muestra la app.</li>
  </ol>
  <div class="qr" data-qr="<?= e($otpauth) ?>" role="img" aria-label="Código QR para la app de autenticación"></div>
  <p class="small">Clave: <span class="mono"><?= e(trim(chunk_split($secret, 4, ' '))) ?></span></p>
  <p class="small"><a href="<?= e($otpauth) ?>">Abrir en la app de este teléfono</a></p>
  <form method="post" action="/panel/seguridad" class="stack">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="totp_enable">
    <div class="f"><label for="s-code">Código de la app</label><input id="s-code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="6" required></div>
    <button class="btn" type="submit">Activar verificación en dos pasos</button>
  </form>
</section>
<?php elseif ($enabled && !$codes): ?>
<section class="card stack">
  <h2 class="d">Verificación en dos pasos</h2>
  <p class="small"><span class="pill good">Activa</span> Te quedan <?= e((string) $remaining) ?> códigos de respaldo sin usar.</p>
  <form method="post" action="/panel/seguridad"><?= csrf_field() ?><input type="hidden" name="action" value="backup_codes"><button class="btn ghost" type="submit">Generar códigos nuevos</button></form>
</section>
<?php endif; ?>

<?php if (!$setupOnly): ?>
<section class="card stack" aria-labelledby="tpw">
  <h2 id="tpw" class="d">Cambiar contraseña</h2>
  <form method="post" action="/panel/seguridad" class="fg">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="password">
    <div class="f full"><label for="c0">Contraseña actual</label><input id="c0" type="password" name="current" autocomplete="current-password" required></div>
    <div class="f"><label for="c1">Nueva contraseña</label><input id="c1" type="password" name="password" autocomplete="new-password" minlength="12" required></div>
    <div class="f"><label for="c2">Repite la nueva</label><input id="c2" type="password" name="password_confirm" autocomplete="new-password" required></div>
    <div class="full"><button class="btn" type="submit">Guardar</button></div>
  </form>
</section>
<?php endif; ?>
<script src="<?= e(asset('vendor/qrcode.js')) ?>" defer></script>
<script src="<?= e(asset('js/qr.js')) ?>" defer></script>
