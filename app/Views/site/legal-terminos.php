<?php
/**
 * Términos, reservas y abonos (§1.3, §11).
 * @var list<array{0:string,1:?string}> $crumbs @var string $vigencia @var array<string,mixed> $b
 */
$minDays = (int) config('app.booking.min_days_ahead', 2);
$deposit = (int) config('app.booking.deposit_percent', 50);
$toc = [
    'reservas' => 'Reservas',
    'abono' => 'Abono',
    'cambios' => 'Cambios y reagendamiento',
    'domicilio' => 'Servicio a domicilio',
    'cursos' => 'Cursos',
    'bonos' => 'Bonos de regalo',
    'retracto' => 'Derecho de retracto',
    'contacto' => 'Contacto',
];
?>
<section class="wrap legal-head">
  <?= App\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
  <h1>
    <span class="lbl c-terracota">Políticas de reserva</span>
    <span class="d">Términos, reservas y abonos.</span>
  </h1>
  <p class="lead mt-16">Las reglas claras para separar tu fecha, cambiarla si lo necesitas e inscribirte en un curso o regalar un bono.</p>
  <div class="legal-meta mt-16">
    <p>Anticipación mínima<strong><?= e((string) $minDays) ?> días</strong></p>
    <p>Abono para separar<strong><?= e((string) $deposit) ?> %</strong></p>
    <p>Vigente desde<strong><?= e($vigencia) ?></strong></p>
  </div>
</section>

<div class="wrap legal-layout">
  <nav class="legal-toc" aria-label="Contenido">
    <?php foreach ($toc as $id => $label): ?><a href="#<?= e($id) ?>"><?= e($label) ?></a><?php endforeach; ?>
  </nav>
  <article class="prose">
    <h2 id="reservas">1. Reservas</h2>
    <ul>
      <li>Las citas se agendan con <strong>mínimo <?= e((string) $minDays) ?> días de anticipación</strong>, por WhatsApp. En <a href="/reservar">/reservar</a> eliges servicio, fecha y lugar, y se abre WhatsApp con tu solicitud ya escrita.</li>
      <li>La web no guarda tus datos: la solicitud solo viaja en el mensaje que tú envías.</li>
      <li>Por WhatsApp te confirmamos la disponibilidad. La fecha queda separada cuando recibimos el abono.</li>
    </ul>

    <h2 id="abono">2. Abono</h2>
    <ul>
      <li>La fecha se confirma con un <strong>abono del <?= e((string) $deposit) ?> %</strong> del valor total, por <strong>Nequi o Bancolombia</strong>. El saldo se paga el día del servicio.</li>
      <li>Los datos de pago no se publican en la web: te los enviamos por WhatsApp.</li>
      <li>El comprobante del abono se envía por el mismo chat.</li>
      <li><strong>El abono no es reembolsable.</strong> Si no puedes asistir, reagendamos tu cita y el abono se conserva para la nueva fecha.</li>
    </ul>

    <h2 id="cambios">3. Cambios y reagendamiento</h2>
    <ul>
      <li>Los cambios de fecha u hora se avisan con <strong>mínimo 1 día de anticipación</strong>, sujetos a disponibilidad.</li>
      <li>Si no asistes sin avisar, el abono se pierde.</li>
      <li>Si por fuerza mayor tenemos que mover tu cita, te proponemos una nueva fecha y el abono se mantiene completo.</li>
    </ul>

    <h2 id="domicilio">4. Servicio a domicilio</h2>
    <p>Atendemos a domicilio en Bucaramanga, Floridablanca, Piedecuesta y Girón. Necesitamos un espacio con buena luz, una mesa y un tomacorriente cerca.</p>
    <p>El domicilio siempre se cobra aparte del servicio, según el municipio: <?php $hf = (array) config('app.booking.home_fees'); asort($hf); echo e(implode(', ', array_map(static fn(string $t, int $v): string => $t . ' ' . money($v), array_keys($hf), $hf))); ?>. Son valores de referencia y pueden cambiar según la ubicación exacta. En el estudio no tiene costo adicional.</p>
    <p>Las áreas rurales, los servicios fuera del área metropolitana y los horarios especiales se acuerdan por WhatsApp según la distancia y la hora.</p>

    <h2 id="cursos">5. Cursos</h2>
    <ul>
      <li>Los cursos se dictan en el estudio de Floridablanca. Te inscribes por WhatsApp y el cupo se separa con un abono del <?= e((string) $deposit) ?> %.</li>
      <li>Las fechas y horarios del próximo grupo te los enviamos por WhatsApp.</li>
      <li>El curso de maquillaje profesional requiere que la alumna lleve modelo de práctica.</li>
      <li>Si no puedes asistir a un módulo, coordinamos su reposición según disponibilidad del grupo.</li>
    </ul>

    <h2 id="bonos">6. Bonos de regalo</h2>
    <ul>
      <li>Los bonos se piden por WhatsApp y se entregan en tarjeta física o digital con el nombre de quien lo recibe.</li>
      <li>Se canjean agendando el servicio o curso del bono, con la misma anticipación mínima de cualquier reserva. Al usarlo, no se pide abono.</li>
      <li>La vigencia se indica en el bono.</li>
      <li>Los bonos no se cambian por dinero y no son reembolsables, salvo en los casos previstos por la ley (por ejemplo, el derecho de retracto).</li>
    </ul>

    <h2 id="retracto">7. Derecho de retracto</h2>
    <p>Según el artículo 47 de la Ley 1480 de 2011 (Estatuto del Consumidor), en los bonos y cursos pagados a distancia tienes <strong>5 días hábiles</strong> desde el pago para retractarte, siempre que el servicio no haya empezado a prestarse ni el bono se haya canjeado. Te devolvemos el dinero dentro de los 30 días calendario siguientes.</p>
    <p>Para ejercerlo, escríbenos a <a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a> o por WhatsApp. Los abonos de servicios se rigen por la sección de abono.</p>

    <h2 id="contacto">8. Contacto</h2>
    <p><?= e($b['legal_name']) ?> · <?= e($b['address']) ?>, <?= e($b['city']) ?>, <?= e($b['region']) ?> · WhatsApp <?= e($b['phone_display']) ?> · <a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a>.</p>
    <p>El tratamiento de tus datos se rige por la <a href="/politica-de-privacidad">política de tratamiento de datos</a>.</p>
  </article>
</div>
