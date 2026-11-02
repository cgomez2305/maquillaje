<?php
/** Banner de consentimiento (§13). GA4/GTM solo se carga tras «Aceptar». */
$gtm = (string) config('app.analytics.gtm_id', '');
?>
<section class="consent" id="consent" hidden aria-label="Preferencias de cookies" data-gtm="<?= e($gtm) ?>">
  <p class="lbl">Tu privacidad</p>
  <p>Usamos cookies propias para que el sitio funcione y, si lo aceptas, cookies de analítica de Google para entender qué páginas te sirven. <a href="/politica-de-privacidad">Política de datos</a>.</p>
  <form class="stack gap-12" data-consent-form hidden>
    <fieldset>
      <legend class="sr-only">Tipos de cookies</legend>
      <label class="ckb"><input type="checkbox" checked disabled> Necesarias (siempre activas)</label>
      <label class="ckb"><input type="checkbox" name="analytics" value="1"> Analítica (Google Analytics 4)</label>
    </fieldset>
    <div class="actions"><button class="btn" type="submit">Guardar preferencias</button></div>
  </form>
  <div class="actions" data-consent-actions>
    <button class="btn" type="button" data-consent="accept">Aceptar</button>
    <button class="btn ghost" type="button" data-consent="reject">Rechazar</button>
    <button class="btn ghost" type="button" data-consent="config">Configurar</button>
  </div>
</section>
