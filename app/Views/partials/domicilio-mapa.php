<?php
/**
 * Tarifas de domicilio sobre un mapa real (Leaflet + teselas de CARTO/OpenStreetMap).
 * Sin JavaScript queda la lista de tarifas completa; mapa.js carga Leaflet solo cuando el bloque
 * se acerca a la pantalla y enlaza lista y mapa (tocar un municipio lo resalta en los dos).
 * @var string $origin  origen del clic a WhatsApp
 */
$fees = (array) config('app.booking.home_fees');
asort($fees);
$b = config('app.business');
$tier = static fn(int $fee): string => $fee <= 25000 ? 't1' : ($fee <= 30000 ? 't2' : 't3');
?>
<div class="dmap" data-dmap>
  <div class="dmap-side">
    <ul class="dmap-list" aria-label="Tarifas de domicilio por municipio">
      <li class="dmap-studio">
        <span class="dmap-sw dmap-sw-pin" aria-hidden="true"><?= icon('pin') ?></span>
        <span class="dmap-n">En el estudio<small><?= e($b['address']) ?>, Floridablanca</small></span>
        <span class="dmap-p">Sin costo</span>
      </li>
      <?php foreach ($fees as $town => $fee): ?>
      <li>
        <button type="button" class="dmap-z" data-zona="<?= e($town) ?>" aria-pressed="false">
          <span class="dmap-sw <?= $tier((int) $fee) ?>" aria-hidden="true"></span>
          <span class="dmap-n"><?= e($town) ?><small>Domicilio adicional</small></span>
          <span class="dmap-p"><?= e(money((int) $fee)) ?></span>
        </button>
      </li>
      <?php endforeach; ?>
    </ul>
    <p class="dmap-note">El domicilio se suma al valor del servicio. Áreas rurales a convenir; el valor puede cambiar según la ubicación exacta.</p>
  </div>
  <div class="dmap-map" data-dmap-canvas role="region" aria-label="Mapa de zonas de domicilio en el área metropolitana de Bucaramanga">
    <p class="dmap-wait">Bucaramanga · Floridablanca · Girón · Piedecuesta</p>
  </div>
</div>
