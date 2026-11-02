<?php /** @var bool $invalid @var ?string $error */ ?>
<div>
  <p class="lbl">Acceso privado</p>
  <h1 class="d auth-title"><?= $invalid ? 'Enlace vencido' : 'Nueva contraseña' ?></h1>
</div>
<?php if ($invalid): ?>
<p class="small muted">El enlace ya se usó o caducó (dura 30 minutos). Pide uno nuevo.</p>
<a class="btn block" href="/panel/recuperar">Pedir otro enlace</a>
<?php else: ?>
<?php if ($error): ?><div class="flash err" role="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post" novalidate>
  <?= csrf_field() ?>
  <div class="f"><label for="p1">Nueva contraseña</label><input id="p1" type="password" name="password" autocomplete="new-password" minlength="12" required><span class="hint">Mínimo 12 caracteres. Una frase larga es más segura.</span></div>
  <div class="f"><label for="p2">Repite la contraseña</label><input id="p2" type="password" name="password_confirm" autocomplete="new-password" required></div>
  <button class="btn block" type="submit">Guardar contraseña</button>
</form>
<?php endif; ?>
