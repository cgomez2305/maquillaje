<?php /** Cabecera mínima del flujo de reserva (maqueta Reservar.dc.html). */ ?>
<header class="nav">
  <div class="wrap nav-in">
    <a class="brand" href="/" aria-label="Make Up by Fletcher, inicio"><img src="<?= e(img_src('marca/isotipo-negativo', 80)) ?>" alt="" width="40" height="40"><span class="d">Fletcher</span></a>
    <div class="nav-actions gap-18">
      <span class="hide-m fs-13 c-topo">¿Prefieres escribir?</span>
      <a class="btn ghost sm" href="<?= e(wa_href('reservar', 'estudio')) ?>" rel="nofollow">WhatsApp</a>
    </div>
  </div>
</header>
