<?php
/**
 * Política de tratamiento de datos personales (Ley 1581 de 2012, Decreto 1377 de 2013).
 * @var list<array{0:string,1:?string}> $crumbs @var string $vigencia @var array<string,mixed> $b
 */
$toc = [
    'responsable' => 'Responsable',
    'datos' => 'Datos que recolectamos',
    'finalidades' => 'Finalidades',
    'derechos' => 'Tus derechos',
    'procedimiento' => 'Cómo ejercerlos',
    'conservacion' => 'Conservación y supresión',
    'encargados' => 'Encargados',
    'cookies' => 'Cookies y analítica',
    'seguridad' => 'Seguridad',
    'vigencia' => 'Vigencia',
];
?>
<section class="wrap legal-head">
  <?= App\View::partial('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
  <h1>
    <span class="lbl c-terracota">Ley 1581 de 2012 · Decreto 1377 de 2013</span>
    <span class="d">Política de tratamiento de datos.</span>
  </h1>
  <p class="lead mt-16">Cómo cuidamos los datos que nos compartes al reservar, cotizar o comprar, para qué los usamos y cómo puedes consultarlos, corregirlos o pedir que los borremos.</p>
  <div class="legal-meta mt-16">
    <p>Responsable<strong><?= e($b['legal_name']) ?></strong></p>
    <p>Contacto<strong><?= e($b['email']) ?></strong></p>
    <p>Vigente desde<strong><?= e($vigencia) ?></strong></p>
  </div>
</section>

<div class="wrap legal-layout">
  <nav class="legal-toc" aria-label="Contenido">
    <?php foreach ($toc as $id => $label): ?><a href="#<?= e($id) ?>"><?= e($label) ?></a><?php endforeach; ?>
  </nav>
  <article class="prose">
    <h2 id="responsable">1. Responsable del tratamiento</h2>
    <p><strong><?= e($b['legal_name']) ?></strong>, marca de la maquilladora <strong><?= e($b['owner']) ?></strong>, con estudio en <?= e($b['address']) ?>, <?= e($b['city']) ?>, <?= e($b['region']) ?>, Colombia.</p>
    <ul>
      <li>Correo para asuntos de datos personales: <a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a></li>
      <li>WhatsApp: <?= e($b['phone_display']) ?></li>
      <li>Sitio web: maquillajebucaramanga.com</li>
      <li>Identificación del responsable: <span class="pending">[Cédula o NIT pendiente]</span></li>
    </ul>

    <h2 id="datos">2. Datos que recolectamos</h2>
    <p>El sitio <strong>no guarda los datos que escribes en los formularios</strong>. Cuando pides una reserva, una cotización, un curso, un bono o la guía para novias, el sitio arma el mensaje y lo abre en tu WhatsApp; solo nos llega si tú lo envías. A partir de ese chat tratamos:</p>
    <ul>
      <li><strong>Solicitudes de servicio:</strong> nombre, número de WhatsApp, servicio, fecha, hora, lugar (barrio y ciudad si es a domicilio), tipo de evento y las notas que quieras dejar.</li>
      <li><strong>Comprobantes de abono</strong> que nos envías para confirmar tu fecha.</li>
      <li><strong>Datos técnicos del sitio:</strong> conteos anónimos de visitas y clics (sin IP, cookies ni identificadores) y, solo si lo aceptas, la analítica de Google.</li>
    </ul>

    <h2 id="finalidades">3. Finalidades</h2>
    <ul>
      <li>Responder tu solicitud, confirmar la disponibilidad, enviarte los datos para el abono, recordatorios y avisos de cambios.</li>
      <li>Coordinar cursos y bonos de regalo.</li>
      <li>Comunicarnos contigo por WhatsApp, correo o teléfono sobre el servicio que pediste.</li>
      <li>Cumplir obligaciones legales y tributarias.</li>
      <li>Enviarte novedades y promociones <strong>solo si lo autorizas</strong>. Puedes retirar esta autorización cuando quieras.</li>
      <li>Medir de forma agregada el uso del sitio para mejorarlo (solo con tu consentimiento de cookies de analítica).</li>
    </ul>

    <h2 id="derechos">4. Tus derechos como titular</h2>
    <p>De acuerdo con el artículo 8 de la Ley 1581 de 2012, puedes:</p>
    <ul>
      <li><strong>Conocer</strong> los datos que tenemos sobre ti y cómo los usamos.</li>
      <li><strong>Actualizar y rectificar</strong> datos incompletos, inexactos o desactualizados.</li>
      <li><strong>Solicitar prueba</strong> de la autorización que nos diste.</li>
      <li><strong>Revocar la autorización</strong> y <strong>pedir la supresión</strong> de tus datos cuando no exista un deber legal o contractual de conservarlos.</li>
      <li>Presentar quejas ante la <strong>Superintendencia de Industria y Comercio</strong> después de haber agotado el trámite con nosotros.</li>
      <li>Acceder gratuitamente a tus datos.</li>
    </ul>

    <h2 id="procedimiento">5. Cómo ejercer tus derechos</h2>
    <p>Escríbenos a <a href="mailto:<?= e($b['email']) ?>"><?= e($b['email']) ?></a> con el asunto «Datos personales», tu nombre, el número de WhatsApp con el que nos escribiste, la solicitud concreta y, si es un reclamo, los hechos y documentos que lo soportan.</p>
    <div class="box">
      <p><strong>Consultas:</strong> respondemos en un máximo de 10 días hábiles desde que recibimos la solicitud, prorrogables 5 días hábiles más avisándote el motivo.</p>
      <p><strong>Reclamos</strong> (corrección, actualización, supresión o revocatoria): respondemos en un máximo de 15 días hábiles, prorrogables 8 días hábiles más avisándote el motivo. Si el reclamo está incompleto, te pediremos completarlo dentro de los 5 días siguientes.</p>
    </div>
    <p>Para proteger tu información, verificaremos tu identidad antes de entregar o modificar datos.</p>

    <h2 id="conservacion">6. Conservación y supresión</h2>
    <p>Conservamos tus datos mientras sean necesarios para las finalidades descritas. Cuando pides la supresión, borramos la conversación y los datos de contacto, salvo la información contable que la ley nos obliga a guardar.</p>

    <h2 id="encargados">7. Encargados y transferencias</h2>
    <p>Para operar el sitio compartimos datos estrictamente necesarios con proveedores que actúan como encargados del tratamiento:</p>
    <ul>
      <li><strong>Hostinger:</strong> alojamiento del sitio y servicio de correo.</li>
      <li><strong>Google (Google Analytics y Google Tag Manager):</strong> analítica del sitio, solo si la aceptas.</li>
      <li><strong>WhatsApp (Meta):</strong> el canal por el que nos envías tus solicitudes.</li>
    </ul>
    <p>Algunos de estos proveedores pueden almacenar información fuera de Colombia, en países con niveles adecuados de protección o bajo cláusulas contractuales que la garantizan.</p>

    <h2 id="cookies">8. Cookies y analítica</h2>
    <p>Usamos cookies <strong>necesarias</strong> para que el sitio funcione (sesión del formulario y protección contra envíos falsos).</p>
    <p>Las cookies de <strong>analítica</strong> de Google solo se cargan si las aceptas en el aviso de cookies. Puedes cambiar tu decisión en cualquier momento desde el enlace «Cookies» al pie de cada página.</p>

    <h2 id="seguridad">9. Seguridad</h2>
    <p>El sitio funciona solo con conexión cifrada (HTTPS) y no almacena los datos de los formularios. Los comprobantes y conversaciones se manejan únicamente en WhatsApp.</p>

    <h2 id="vigencia">10. Vigencia y cambios</h2>
    <p>Esta política rige desde el <?= e($vigencia) ?>. Si la cambiamos de forma sustancial, lo publicaremos en esta página y, si tenemos tu correo, te avisaremos antes de aplicar los cambios.</p>
  </article>
</div>
