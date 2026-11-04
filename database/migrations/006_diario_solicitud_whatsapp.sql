-- Diario: el artículo de precios de novia describía la reserva en línea con correo y dirección.
-- Ahora la solicitud se arma en /reservar y se envía por WhatsApp (sin guardar datos en la web).
UPDATE posts
SET body_html = REPLACE(body_html,
  '<a href="/reservar">Reserva en línea</a> o escríbenos por WhatsApp con tu nombre, teléfono, correo y dirección, y la fecha queda reservada',
  'Arma tu solicitud en <a href="/reservar">Reservar</a> (servicio, fecha y lugar) y se abre WhatsApp con el mensaje listo; la fecha queda reservada')
WHERE body_html LIKE '%Reserva en línea</a> o escríbenos por WhatsApp con tu nombre, teléfono, correo y dirección%';
