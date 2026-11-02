-- Catálogo 2026 (README §1.1, §1.2) y tienda.
SET NAMES utf8mb4;

INSERT INTO services (code, name, price, category, active) VALUES
 ('pack1', 'Pack 1 · Servicio base (maquillaje social)', 200000, 'service', 1),
 ('pack2', 'Pack 2 · Full experience a domicilio', 250000, 'service', 1),
 ('pack3', 'Pack 3 · Novias y quinceañeras', 600000, 'service', 1),
 ('curso-auto', 'Curso de automaquillaje', 400000, 'course', 1),
 ('curso-pro', 'Curso de maquillaje profesional', 1500000, 'course', 1);

-- Productos de la tienda. Los de precio pendiente quedan inactivos (no se pueden comprar)
-- hasta que la dueña cargue el precio en Ajustes (README §15).
INSERT INTO products (slug, name, category, subcategory, service_code, price, stock, active, summary, description, image, sort) VALUES
 ('bono-maquillaje-social', 'Bono · Maquillaje social', 'gift', 'bonos', 'pack1', 200000, NULL, 1,
  'Asesoría, maquillaje social y pestañas en el estudio.',
  'Bono de regalo canjeable por el Pack 1: asesoría previa con análisis de rasgos, maquillaje social y pestañas postizas de un solo uso en el estudio de Floridablanca. Se entrega en tarjeta digital con el nombre de quien lo recibe y un código único BONO.',
  'fotos/ig-maquillaje-social', 10),
 ('bono-full-experience', 'Bono · Full experience', 'gift', 'bonos', 'pack2', 250000, NULL, 1,
  'Skin care, silla y luz de estudio en casa, pestañas y kit de retoque.',
  'Bono de regalo canjeable por el Pack 2 a domicilio: preparación de piel con mascarilla y masaje, música relajante, silla y luz de estudio, pestañas postizas y kit de retoque. Se entrega en tarjeta digital con el nombre de quien lo recibe y un código único BONO.',
  'fotos/ig-novia-reel', 20),
 ('bono-curso-automaquillaje', 'Bono · Curso de automaquillaje', 'gift', 'bonos', 'curso-auto', 400000, NULL, 1,
  'Cinco módulos en el estudio para aprender a maquillarse como una profesional.',
  'Bono de regalo canjeable por el curso de automaquillaje: cinco módulos de 1 a 1,5 horas en el estudio de Floridablanca. Se entrega en tarjeta digital con el nombre de quien lo recibe y un código único BONO.',
  'fotos/catalogo-alumna-curso', 30),
 ('curso-automaquillaje', 'Curso de automaquillaje', 'course', 'cursos', 'curso-auto', 400000, NULL, 1,
  '5 módulos de 1 a 1,5 h en el estudio de Floridablanca.',
  'Teoría básica, piel, ojos día, ojos noche, ojos glam, cejas y labios. Al inscribirte recibes la lista de materiales con descuento en la tienda aliada.',
  'fotos/catalogo-alumna-curso', 40),
 ('curso-maquillaje-profesional', 'Curso de maquillaje profesional', 'course', 'cursos', 'curso-pro', 1500000, NULL, 1,
  '7 módulos de 2 a 3 h. Requiere modelo de práctica.',
  'Teoría básica, piel, social día, noche y glam, novias, quinceañeras, editorial, sesión de fotos, cejas y labios. Requiere modelo de práctica.',
  'fotos/catalogo-maria-paula-tablero', 50),
 ('kit-de-retoque', 'Kit de retoque', 'product', 'kits', NULL, 0, NULL, 0,
  'Papel de arroz, brillo labial y espejo de bolsillo.', NULL, NULL, 60),
 ('kit-de-inicio-alumnas', 'Kit de inicio para alumnas', 'product', 'kits', NULL, 0, NULL, 0,
  'Los materiales de la lista del curso, listos para la primera clase.', NULL, NULL, 70);

INSERT INTO settings (name, value, updated_at) VALUES
 ('booking_slots', '["05:00","07:00","09:00","11:00","14:00","16:00"]', UTC_TIMESTAMP()),
 ('closed_weekdays', '[]', UTC_TIMESTAMP());
