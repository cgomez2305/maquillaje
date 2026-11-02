<?php /** @var ?string $error */ ?>
<div>
  <p class="lbl">Paso 2 de 2</p>
  <h1 class="d auth-title">Verificación en dos pasos</h1>
</div>
<p class="small muted">Escribe el código de 6 dígitos de tu app de autenticación, o uno de tus códigos de respaldo.</p>
<?php if ($error): ?><div class="flash err" role="alert"><?= e($error) ?></div><?php endif; ?>
<form method="post" action="/panel/login/2fa" novalidate>
  <?= csrf_field() ?>
  <div class="f"><label for="l-code">Código</label><input id="l-code" name="code" inputmode="numeric" autocomplete="one-time-code" maxlength="11" required autofocus></div>
  <button class="btn block" type="submit">Entrar</button>
</form>
<p class="small"><a href="/panel/login">Volver</a></p>
