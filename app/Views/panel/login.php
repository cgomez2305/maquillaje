<?php /** @var ?string $error @var string $email */ $content_is_form = true; ?>
<div>
  <p class="lbl">Acceso privado</p>
  <h1 class="d auth-title">Entrar al panel</h1>
</div>
<?php if ($error): ?><div class="flash err" role="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="/panel/login" novalidate>
  <?= csrf_field() ?>
  <div class="f"><label for="l-email">Correo</label><input id="l-email" type="email" name="email" autocomplete="username" required value="<?= e($email) ?>" autofocus></div>
  <div class="f"><label for="l-pass">Contraseña</label><input id="l-pass" type="password" name="password" autocomplete="current-password" required></div>
  <button class="btn block" type="submit">Continuar</button>
</form>
<p class="small muted"><a href="/panel/recuperar">¿Olvidaste tu contraseña?</a></p>
