<?php
/**
 * Términos, reservas y abonos (§1.3, §11).
 * @var list<array{0:string,1:?string}> $crumbs @var string $vigencia @var array<string,mixed> $b
 */
$minDays = (int) config('app.booking.min_days_ahead', 2);
$hold = (int) config('app.booking.hold_hours', 48);
$deposit = (int) config('app.booking.deposit_percent', 50);
$toc = [
    'reservas' => 'Reservas',
    'abono' => 'Abono',
    'cambios' => 'Cambios y reagendamiento',
    'domicilio' => 'Servicio a domicilio',
    'cursos' => 'Cursos',
    'tienda' => 'Compras en la tienda',
    'bonos' => 'Bonos de regalo',
    'retracto' => 'Derecho de retracto',
    'contacto' => 'Contacto',
];
?>
<section class="wrap legal-head">
  <?= App\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
  <h1>
    <span class="lbl in c-terracota">Políticas de reserva y compra</span>
    <span class="d in d1">Términos, reservas y abonos.</span>
  </h1>
  <p class="lead in d2 mt-16">Las reglas claras para separar tu fecha, cambiarla si lo necesitas y comprar cursos, bonos y productos en línea.</p>
  <div class="legal-meta in d3 mt-16">
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
      <li>Las citas se agendan con <strong>mínimo <?= e((string) $minDays) ?> días de anticipación</strong>, en línea en <a href="/reservar">/reservar</a> o por WhatsApp.</li>
      <li>Para reservar pedimos nombre, teléfono, correo, dirección (si es a domicilio) y fecha de cumpleaños.</li>
      <li>Al reservar en línea recibes un código <strong>MBF-…</strong> y un enlace para consultar el estado de tu reserva.</li>
      <li>La reserva queda en estado «solicitada» y el horario se <strong>retiene durante <?= e((string) $hold) ?> horas</strong>. Si en ese plazo no recibimos el abono, la reserva vence y el cupo se libera.</li>
    </ul>

    <h2 id="abono">2. Abono</h2>
    <ul>
      <li>La fecha se confirma con un <strong>abono del <?= e((string) $deposit) ?> %</strong> del valor total, por <strong>Nequi o Bancolombia</strong>. El saldo se paga el día del servicio.</li>
      <li>Los datos de pago no se publican en la web: te los enviamos en el correo de confirmación y por WhatsApp.</li>
      <li>El comprobante se envía por WhatsApp indicando el código de la reserva.</li>
      <li><strong>El abono no es reembolsable.</strong> Si no puedes asistir, reagendamos tu cita y el abono se conserva para la nueva fecha.</li>
    </ul>

    <h2 id="cambios">3. Cambios y reagendamiento</h2>
    <ul>
      <li>Los cambios de fecha u hora se avisan con <strong>mínimo 1 día de anticipación</strong>, sujetos a disponibilidad.</li>
      <li>Si el aviso llega con menos de 1 día, se aplica una penalización de <span class="pending">[monto o porcentaje pendiente]</span>.</li>
      <li>Si no asistes sin avisar, el abono se pierde.</li>
      <li>Si por fuerza mayor tenemos que mover tu cita, te proponemos una nueva fecha y el abono se mantiene completo.</li>
    </ul>

    <h2 id="domicilio">4. Servicio a domicilio</h2>
    <p>Atendemos a domicilio en Bucaramanga, Floridablanca, Piedecuesta y Girón. Necesitamos un espacio con buena luz, una mesa y un tomacorriente cerca.</p>
    <p>Condiciones y costo de desplazamiento fuera del área metropolitana o en horarios especiales: <span class="pending">[condiciones y tarifas pendientes]</span>.</p>

    <h2 id="cursos">5. Cursos</h2>
    <ul>
      <li>Los cursos se dictan en el estudio de Floridablanca. El cupo se asegura con el pago en línea.</li>
      <li>Fechas y horarios de los grupos: <span class="pending">[próximos grupos pendientes]</span>.</li>
      <li>El curso de maquillaje profesional requiere que la alumna lleve modelo de práctica.</li>
      <li>Si no puedes asistir a un módulo, coordinamos su reposición según disponibilidad del grupo.</li>
    </ul>

    <h2 id="tienda">6. Compras en la tienda</h2>
    <ul>
      <li>Los pagos en línea se procesan con <strong>Wompi</strong> (Bancolombia): tarjeta, PSE, Nequi y otros medios. Nunca vemos ni guardamos los datos de tu tarjeta.</li>
      <li>El precio que pagas es el que muestra la página al momento de la compra, en pesos colombianos e incluye los impuestos aplicables.</li>
      <li>Tu compra se confirma solo cuando Wompi aprueba el pago; te avisamos por correo.</li>
      <li>Los productos se recogen en el estudio o se envían a la dirección indicada. Costos y tiempos de envío: <span class="pending">[pendiente]</span>.</li>
    </ul>

    <h2 id="bonos">7. Bonos de regalo</h2>
    <ul>
      <li>Cada bono tiene un código único <strong>BONO-XXXX-XXXX</strong> y el nombre de quien lo recibe.</li>
      <li>Se canjea agendando el servicio o curso del bono, con la misma anticipación mínima de cualquier reserva. Al usarlo, no se pide abono.</li>
      <li>Vigencia del bono: <span class="pending">[pendiente]</span> desde la fecha de compra.</li>
      <li>Los bonos no se cambian por dinero y no son reembolsables, salvo en los casos previstos por la ley (por ejemplo, el derecho de retracto).</li>
    </ul>

    <h2 id="retracto">8. Derecho de retracto</h2>
    <p>Según el artículo 47 de la Ley 1480 de 2011 (Estatuto del Consumidor), en las compras hechas en línea tienes <strong>5 días hábiles</strong> desde la compra para retractarte, siempre que el servicio no haya empezado a prestarse ni el bono se haya canjeado, y que los productos se devuelvan sin uso y en su empaque. Te devolvemos el dinero dentro de los 30 días calendario siguientes.</p>
    <p>Para ejercerlo, escríbenos a <a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a> con la referencia del pedido (PED-…). Las reservas de servicios con abono por transferencia no son compras en línea y se rigen por la sección de abono.</p>

    <h2 id="contacto">9. Contacto</h2>
    <p><?= e($b['legal_name']) ?> · <?= e($b['address']) ?>, <?= e($b['city']) ?>, <?= e($b['region']) ?> · WhatsApp <?= e($b['phone_display']) ?> · <a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a>.</p>
    <p>El tratamiento de tus datos se rige por la <a href="/politica-de-privacidad">política de tratamiento de datos</a>.</p>
  </article>
</div>
