# Despliegue en Hostinger

Guía paso a paso para publicar maquillajebucaramanga.com (README §16). Hazlo primero en un subdominio de pruebas.

## 1. PHP y extensiones

En hPanel › Avanzado › Configuración de PHP: **PHP 8.3** con `pdo_mysql`, `mbstring`, `intl`, `sodium`, `fileinfo`, `gd`, `openssl`, `curl`. El archivo `public/.user.ini` ya desactiva `expose_php` y `display_errors`.

## 2. Archivos

Opción A (recomendada): apuntar el dominio a la carpeta `public/` del proyecto (hPanel › Dominios › carpeta raíz).

Opción B: subir el contenido de `public/` a `public_html/` y el resto del proyecto a `~/maquillaje/` (un nivel arriba, fuera de la web). `public/index.php` detecta `~/maquillaje` automáticamente.

Si por error se sube todo el repositorio a `public_html/`, el `.htaccess` de la raíz redirige todo a `public/` y bloquea `app/`, `config/`, `storage/`, `vendor/`, etc.

Luego, por SSH en la carpeta del proyecto:

```bash
composer install --no-dev --optimize-autoloader
composer audit
cp .env.example .env && chmod 600 .env
```

Completa `.env`: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `APP_KEY` (32 bytes en base64), base de datos, SMTP, `OWNER_EMAIL`, `PARTNER_EMAIL`, datos del abono (`PAY_*`, solo van por correo), llaves de Wompi y GA4/GTM.

Permisos: `storage/` escribible por PHP (`chmod -R 750 storage`), `.env` 600.

## 3. Base de datos

1. Crea la base y un usuario en hPanel › Bases de datos (utf8mb4).
2. Ejecuta las migraciones con el usuario de migraciones: `php cron/migrate.php`.
3. Usuarios del panel, siempre por consola:
   ```bash
   php cron/crear-usuario.php --email=… --nombre="María Paula Fletcher" --rol=owner
   php cron/crear-usuario.php --email=… --nombre="Carlos Gómez" --rol=partner
   ```
   Restablecer el 2FA de alguien que perdió el teléfono y sus códigos: añade `--reset-2fa`.

Las tablas `booking_events`, `payment_events` y `audit_log` son de solo inserción y los periodos de comisión cerrados son inalterables: lo garantizan triggers (funcionan en hosting compartido aunque no se puedan separar permisos por tabla). Si el hosting lo permite, crea además un usuario `app_rw` sin `UPDATE`/`DELETE` en esas tablas y deja el usuario con todos los permisos solo para migraciones (`DB_MIGRATE_USER`).

## 4. Correo

Crea `reservas@maquillajebucaramanga.com` en Hostinger, configura SPF, DKIM y DMARC en la zona DNS (hPanel › Correos › Configuración) y pon los datos en `MAIL_*`. Prueba con una reserva real: deben llegar el correo de la clienta y el de la dueña.

## 5. Cron (hPanel › Avanzado › Cron Jobs)

Usa la ruta absoluta de PHP 8.3 que muestra hPanel (por ejemplo `/usr/bin/php`).

| Frecuencia | Comando | Función |
|---|---|---|
| `*/15 * * * *` | `php ~/maquillaje/cron/recordatorios.php` | Recordatorio de 24 h y vencimiento de cupos sin abono (48 h) |
| `5 * * * *` | `php ~/maquillaje/cron/encuestas.php` | Encuestas post-servicio |
| `10 * * * *` | `php ~/maquillaje/cron/alertas.php` | Alertas al socio (reservas sin marcar) |
| `0 8 * * 1` | `php ~/maquillaje/cron/alertas.php --resumen` | Resumen semanal al socio |
| `10 0 1 * *` | `php ~/maquillaje/cron/cierre-periodo.php` | Corte del mes anterior listo para aprobar |
| `0 3 * * *` | `php ~/maquillaje/cron/sitemap.php && php ~/maquillaje/cron/respaldo.php` | Sitemap y respaldo diario cifrado |
| `30 4 1 * *` | `php ~/maquillaje/cron/retencion.php` | Anonimiza clientas inactivas por más de 3 años |

Los cron corren en hora del servidor (UTC en Hostinger): `10 0 1 * *` UTC equivale a las 19:10 del último día del mes en Bogotá. Para que el corte se genere el día 1 a las 00:10 de Bogotá usa `10 5 1 * *`.

## 6. Respaldos

`cron/respaldo.php` guarda un volcado diario cifrado (clave derivada de `APP_KEY`) en `storage/backups/` y borra los de más de 30 días. **Cópialos fuera del servidor** (Google Drive, otro hosting) y guarda `APP_KEY` en un lugar seguro aparte: sin ella no se pueden descifrar. Prueba la restauración una vez al mes (instrucciones en la cabecera de `cron/respaldo.php`). Activa además los respaldos semanales de archivos de Hostinger.

## 7. SSL, redirecciones y verificación

- Activa SSL en hPanel. `public/.htaccess` fuerza HTTPS y quita el `www` con 301.
- Comprueba las cabeceras en https://securityheaders.com (objetivo A o superior).
- Search Console: verificación por DNS, envía `https://maquillajebucaramanga.com/sitemap.xml`.
- Google Business Profile: en los botones «Sitio web» y «Reservar» usa `?utm_source=gbp&utm_medium=organic` (así se atribuyen como «Google Business»).
- Wompi: en el panel del comercio configura la URL de eventos `https://maquillajebucaramanga.com/webhooks/wompi`, copia las llaves de producción y el secreto de eventos al `.env`. Haz un pago de prueba aprobado y uno rechazado en sandbox antes de pasar a producción (`php tests/WompiTest.php` cubre la lógica).

## 8. Fuente de marca

Cuando esté la licencia web de **Aristotelica Display ExtraLight**, sube `aristotelica-display-extralight.woff2` a `public/assets/fonts/` y añade la línea `url(…) format('woff2')` indicada en `public/assets/css/tokens.css`. Mientras tanto se usa Comfortaa 300 (autoalojada).

## Estado del despliegue actual (2 nov 2026)

Desplegado con la API de Hostinger (MCP) en el plan Business `u480515229`:

- Sitio `maquillajebucaramanga.com`, PHP 8.3 con `sodium` activado, `display_errors` y `expose_php` en Off.
- El proyecto completo está en `public_html/` (las subidas por API van ahí). El `.htaccess` de la raíz sirve solo `public/` y bloquea `app/`, `config/`, `storage/`, `vendor/`, `.env`… (verificado: 403).
- Base de datos `u480515229_mubf` con las migraciones aplicadas (triggers incluidos).
- `.env` de producción **solo en el servidor** (`public_html/.env`). Descárgalo desde el administrador de archivos de hPanel y guárdalo en un lugar seguro: contiene `APP_KEY`, sin la cual no se descifran los respaldos.
- Cron (hora del servidor = UTC; Bogotá = UTC−5): recordatorios `*/15 * * * *`, encuestas `5 * * * *`, alertas `10 * * * *`, resumen semanal `0 13 * * 1` (lunes 8:00 Bogotá), cierre de periodo `10 5 1 * *` (día 1, 00:10 Bogotá), sitemap `0 8 * * *`, respaldo `5 8 * * *` (3:05 Bogotá), retención `30 9 1 * *`. Binario: `/opt/alt/php83/usr/bin/php`.
- Redesplegar: generar el paquete (código + `vendor/` sin pruebas de PHPMailer) **incluyendo el `.env` del servidor**, porque el despliegue por archivo reemplaza el contenido de `public_html`.

Pendiente para activar del todo: buzón y SMTP (hoy `MAIL_DRIVER=log`: los correos quedan en `storage/logs/mail.log`), `PARTNER_EMAIL`, datos de abono `PAY_*`, llaves de Wompi y GA4/GTM, y los usuarios del panel.
