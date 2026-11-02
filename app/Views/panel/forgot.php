<?php /** @var bool $sent */ ?>
<div>
  <p class="lbl">Acceso privado</p>
  <h1 class="d auth-title">Recuperar acceso</h1>
</div>
<?php if ($sent): ?>
<div class="flash ok" role="status">Si el correo pertenece a una cuenta del panel, te enviamos un enlace válido por 30 minutos.</div>
<a class="btn ghost block" href="/panel/login">Volver al acceso</a>
<?php else: ?>
<form method="post" action="/panel/recuperar" novalidate>
  <?= csrf_field() ?>
  <div class="f"><label for="r-email">Correo de tu cuenta</label><input id="r-email" type="email" name="email" autocomplete="username" required autofocus></div>
  <button class="btn block" type="submit">Enviar enlace</button>
</form>
<p class="small muted"><a href="/panel/login">Volver</a></p>
<?php endif; ?>
