-- Diario (README §12.5). El artículo de precios se publica completo (texto de Articulo.dc.html);
-- las demás guías quedan como borrador hasta tener su texto.
-- Los precios del cuerpo y de las FAQ usan marcadores {{precio:codigo}} que se reemplazan al mostrar
-- el artículo con el precio vigente de la tabla services, para que nunca queden desactualizados.
SET NAMES utf8mb4;

ALTER TABLE posts
  ADD COLUMN seo_title VARCHAR(70) NULL AFTER title,
  ADD COLUMN kicker VARCHAR(60) NULL AFTER category;

INSERT INTO posts (slug, title, seo_title, meta_description, category, kicker, target_keyword, excerpt, body_html, faq, cover, reading_minutes, links_to, featured, status, published_at, updated_at) VALUES
('precio-maquillaje-de-novia-bucaramanga',
 '¿Cuánto cuesta un maquillaje de novia en Bucaramanga en 2026?',
 'Precio maquillaje de novia en Bucaramanga 2026 · Fletcher',
 'Precio del maquillaje de novia en Bucaramanga en 2026: qué incluye el Pack 3, cuánto suma el cortejo y cómo separar la fecha con el abono del 50 %.',
 'Novias', 'Novias · Precios 2026', 'precio maquillaje de novia',
 'Precios reales, qué incluye cada paquete, cuánto suma el cortejo y cómo separar la fecha sin sorpresas.',
 '<blockquote id="la-respuesta-corta"><p>La respuesta corta</p><p>En Make Up by Fletcher el maquillaje de novia cuesta <strong>{{precio:pack3}}</strong> en 2026. Incluye asesoría, prueba previa con dos propuestas, skin care, maquillaje el día de la boda, pestañas, kit de retoque, video reel de tu preparación y extra fijación.</p></blockquote>
<p>El precio de un maquillaje de novia depende menos del maquillaje en sí y más de todo lo que lo rodea: si hay prueba previa, si la maquilladora se desplaza, si incluye cuidado de la piel y qué tan preparado está para durar doce horas de ceremonia, fotos y fiesta.</p>
<p>Por eso, antes de comparar cifras, conviene comparar qué trae cada paquete. Aquí te contamos cómo lo hacemos en el <a href="/estudio-maquillaje-floridablanca">estudio de Floridablanca</a>.</p>
<h2 id="que-incluye-el-precio">Qué incluye el precio</h2>
<p>El <a href="/maquillaje-de-novia-bucaramanga">Pack 3</a> está diseñado para novias y quinceañeras, que necesitan un maquillaje probado, fotogénico y resistente. Incluye ocho pasos:</p>
<ul>
<li><strong>Asesoría previa.</strong> Analizamos tus rasgos y definimos qué maquillaje te favorece.</li>
<li><strong>Prueba de maquillaje.</strong> Unos días antes del evento, con dos propuestas de estilo para elegir.</li>
<li><strong>Sesión de relajación y preparación de la piel.</strong> Mascarilla húmeda, masaje facial, esencias aromáticas y masaje de manos con cera tibia.</li>
<li><strong>Maquillaje el día del evento</strong> en silla profesional con luz de estudio.</li>
<li><strong>Pestañas postizas</strong> para el día de la boda.</li>
<li><strong>Kit de retoque</strong> con papel de arroz, brillo labial y espejo de bolsillo.</li>
<li><strong>Video reel</strong> de tu preparación.</li>
<li><strong>Extra fijación</strong> para que el maquillaje llegue intacto al final de la noche.</li>
</ul>
<h2 id="que-hace-que-el-precio-cambie">Qué hace que el precio cambie</h2>
<p>Cuando compares cotizaciones en Bucaramanga, revisa estos cuatro puntos:</p>
<ul>
<li><strong>La prueba previa.</strong> Algunos servicios no la incluyen o la cobran aparte. Es lo que evita sorpresas el día de la boda.</li>
<li><strong>El desplazamiento.</strong> Maquillar en tu casa, hotel o finca implica llevar silla, luz y kit completo.</li>
<li><strong>La fijación.</strong> Un maquillaje para una cena no se prepara igual que uno para lágrimas, abrazos y pista de baile.</li>
<li><strong>La temporada.</strong> De octubre a diciembre se concentran bodas y grados, y las fechas se llenan primero.</li>
</ul>
<h2 id="precios-para-el-cortejo">Precios para el cortejo</h2>
<p>La mamá, las damas y las invitadas pueden maquillarse el mismo día para mantener un solo estilo en las fotos:</p>
<table>
<thead><tr><th scope="col">Servicio</th><th scope="col">Incluye</th><th scope="col">Precio</th></tr></thead>
<tbody>
<tr><td>Pack 1 · Servicio base</td><td>Asesoría, maquillaje social y pestañas</td><td>{{precio:pack1}}</td></tr>
<tr><td>Pack 2 · Full experience</td><td>Skin care, silla y luz de estudio en casa, pestañas y kit de retoque</td><td>{{precio:pack2}}</td></tr>
<tr><td>Pack 3 · Novias y quinceañeras</td><td>Todo lo anterior, prueba previa, video reel y extra fijación</td><td>{{precio:pack3}}</td></tr>
</tbody>
</table>
<h2 id="como-separar-la-fecha">Cómo separar la fecha</h2>
<p>Puedes agendar con un mínimo de dos días de anticipación, aunque para una boda conviene hacerlo con más tiempo. <a href="/reservar">Reserva en línea</a> o escríbenos por WhatsApp con tu nombre, teléfono, correo y dirección, y la fecha queda reservada con un <strong>abono del 50 %</strong> por Nequi o Bancolombia.</p>
<p>Si no puedes asistir, reagendamos la cita: el abono no se devuelve, pero se conserva. Avísanos con al menos un día de anticipación.</p>',
 '[{"q":"¿El precio incluye la prueba de maquillaje?","a":"Sí. La prueba con dos propuestas está incluida en los {{precio:pack3}} del Pack 3."},{"q":"¿Cuánto dura el maquillaje de novia?","a":"Usamos extra fijación para que llegue intacto al final del evento, y te llevas un kit de retoque para la noche."},{"q":"¿Sirve el mismo paquete para quinceañeras?","a":"Sí. El Pack 3 está pensado para novias y quinceañeras, con el mismo precio y los mismos pasos."}]',
 'fotos/catalogo-ojo-detalle', 6, '/maquillaje-de-novia-bucaramanga', 1, 'publicado', '2026-11-02 14:00:00', '2026-11-02 14:00:00'),

('prueba-de-maquillaje-de-novia', 'Prueba de maquillaje de novia: cuándo hacerla y qué llevar', NULL,
 'Cuándo hacer la prueba de maquillaje de novia, qué llevar y cómo elegir entre dos propuestas para evitar sorpresas el día de la boda.',
 'Novias', 'Novias', 'prueba de maquillaje novia',
 'El paso que evita sorpresas el día de la boda, explicado paso a paso.', '', NULL,
 'fotos/ig-novia-reel', 5, '/maquillaje-de-novia-bucaramanga', 0, 'borrador', NULL, '2026-11-02 14:00:00'),
('maquillaje-para-boda-de-dia-o-de-noche', 'Maquillaje para boda de día o de noche: cómo cambia el look', NULL,
 'Maquillaje para boda de día o de noche: qué ajustar en piel, ojos y labios según la luz natural, el flash y el salón.',
 'Novias', 'Novias', 'maquillaje para boda',
 'Luz natural, flash y salón: qué ajustar en piel, ojos y labios.', '', NULL,
 'fotos/ig-clienta-bata-seda', 6, '/maquillaje-de-novia-bucaramanga', 0, 'borrador', NULL, '2026-11-02 14:00:00'),
('maquillaje-para-grados', 'Maquillaje para grados: verte bien con toga, flash y calor', NULL,
 'Maquillaje para grados que aguanta la ceremonia, las fotos con flash y la celebración, pensado para el calor de Bucaramanga.',
 'Grados', 'Grados', 'maquillaje para grados',
 'Un look que aguanta la ceremonia, las fotos familiares y la celebración.', '', NULL,
 'fotos/ig-maquillaje-social', 4, '/precios-maquillaje-bucaramanga#grados', 0, 'borrador', NULL, '2026-11-02 14:00:00'),
('maquillaje-para-quinceanera', 'Maquillaje para quinceañera: natural, fotogénico y de su edad', NULL,
 'Maquillaje para quinceañera natural y fotogénico: cómo lograr un look especial sin que se vea mayor de lo que es.',
 'Quinceañeras', 'Quinceañeras', 'maquillaje para quinceañera',
 'Cómo lograr un look especial sin que se vea mayor de lo que es.', '', NULL,
 'fotos/catalogo-modelo-hero', 5, '/maquillaje-de-novia-bucaramanga', 0, 'borrador', NULL, '2026-11-02 14:00:00'),
('maquillaje-que-dure-todo-el-dia-con-calor', 'Cómo hacer que el maquillaje dure todo el día en el calor de Bucaramanga', NULL,
 'Maquillaje de larga duración para el calor de Bucaramanga: preparación, sellado y retoques para doce horas sin brillos.',
 'Tips de piel', 'Tips de piel', 'maquillaje de larga duración',
 'Preparación, sellado y retoques para doce horas sin brillos.', '', NULL,
 'fotos/catalogo-paleta', 7, '/precios-maquillaje-bucaramanga', 0, 'borrador', NULL, '2026-11-02 14:00:00'),
('skin-care-antes-del-maquillaje', 'Skin care antes del maquillaje: por qué es la mitad del resultado', NULL,
 'Skin care antes del maquillaje: mascarilla, masaje e hidratación, lo que hacemos en el estudio antes de la primera brocha.',
 'Tips de piel', 'Tips de piel', 'skin care antes del maquillaje',
 'Mascarilla, masaje e hidratación: lo que hacemos antes de la primera brocha.', '', NULL,
 'fotos/catalogo-ojo-detalle', 5, '/precios-maquillaje-bucaramanga', 0, 'borrador', NULL, '2026-11-02 14:00:00'),
('automaquillaje-errores-comunes-en-la-piel', 'Automaquillaje: errores comunes en la piel y cómo evitarlos', NULL,
 'Errores comunes de automaquillaje en la piel: base de más, corrector mal ubicado y polvo que se marca, con soluciones simples.',
 'Cursos', 'Cursos', 'curso de automaquillaje',
 'Base de más, corrector mal ubicado y polvo que se marca: soluciones simples.', '', NULL,
 'fotos/catalogo-alumna-curso', 6, '/cursos-de-maquillaje-bucaramanga', 0, 'borrador', NULL, '2026-11-02 14:00:00'),
('como-empezar-como-maquilladora-profesional', 'Cómo empezar a trabajar como maquilladora profesional', NULL,
 'Cómo empezar a trabajar como maquilladora profesional: formación, kit inicial y primeras clientas, desde la experiencia del estudio.',
 'Cursos', 'Cursos', 'curso de maquillaje profesional',
 'Formación, kit inicial y primeras clientas, desde la experiencia del estudio.', '', NULL,
 'fotos/catalogo-maria-paula-tablero', 8, '/cursos-de-maquillaje-bucaramanga', 0, 'borrador', NULL, '2026-11-02 14:00:00'),
('vale-la-pena-el-maquillaje-a-domicilio', '¿Vale la pena el maquillaje a domicilio? Lo que debes saber', NULL,
 'Maquillaje a domicilio en Bucaramanga: qué se lleva, cuánto espacio necesitas y cuándo conviene más ir al estudio.',
 'Servicios', 'Servicios', 'maquillaje a domicilio bucaramanga',
 'Qué se lleva, cuánto espacio necesitas y cuándo conviene más ir al estudio.', '', NULL,
 'fotos/catalogo-brindis-estudio', 4, '/precios-maquillaje-bucaramanga#domicilio', 0, 'borrador', NULL, '2026-11-02 14:00:00');
