# Make Up by Fletcher · maquillajebucaramanga.com

Sitio web de **Make Up by Fletcher**, en **PHP 8.3 puro + HTML + CSS + JS** (sin WordPress ni frameworks).

Desde noviembre de 2026 el sitio **no tiene panel de administración ni pagos en línea**: todas las solicitudes (reserva, cotización, inscripción a cursos, bonos, guía para novias) abren WhatsApp con un mensaje predeterminado y no se guardan datos personales en el servidor. La tienda está en «próximamente» (sin indexar).

- Especificación completa (fuente de verdad): [`docs/ESPECIFICACION.md`](docs/ESPECIFICACION.md)
- Maqueta, capturas y recursos de marca: [`docs/entrega/`](docs/entrega)
- Convenciones de código: [`docs/CONVENCIONES.md`](docs/CONVENCIONES.md)
- Despliegue en Hostinger, cron y respaldos: [`docs/DESPLIEGUE.md`](docs/DESPLIEGUE.md)
- Datos pendientes de la marca: sección 15 de la especificación (se ven en el sitio como `[…]` resaltados)

## Puesta en marcha local

Requisitos: PHP 8.2+ con `pdo_mysql`, `mbstring`, `intl`, `sodium`, `fileinfo`, `gd`, `openssl`; MySQL 8 o MariaDB 10.6+; Composer.

```bash
composer install
cp .env.example .env            # completa DB_*, APP_KEY, MAIL_DRIVER=log en local
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"   # valor para APP_KEY
php cron/migrate.php            # crea tablas, triggers y catálogo
php -S 127.0.0.1:8000 -t public tools/dev-router.php
```

- Sitio: http://127.0.0.1:8000
- Datos de demostración (solo desarrollo): `php database/seeds/demo.php`.
- Pruebas contra la BD local: `php tests/run.php` (o `composer test`).
- Imágenes: las variantes AVIF/WebP/JPG ya están en `public/assets/img`. Para regenerarlas: `pip install pillow pillow-avif-plugin && python3 tools/build-images.py`.

## Estructura

```
public/        único directorio web (index.php, .htaccess, assets/)
app/           Kernel, Router, Http/, Controllers/{Site,Panel,Webhooks}, Domain/, Repositories/, Views/, Support/
config/        app.php (negocio), routes.php, seo.php, catalog.php — sin secretos
database/      migrations/*.sql (con triggers), seeds/
storage/       uploads/ (comprobantes), logs/, cache/, backups/ — fuera de la web
cron/          migrate, sitemap, respaldo
tests/         pruebas del dominio (php tests/run.php)
tools/         utilidades de desarrollo (router local, imágenes, capturas)
```

## Qué incluye

- Inicio, Novias, Servicios y precios, Cursos, Portafolio (filtro + visor), Estudio, Tienda (próximamente), Diario y artículo, legales, sitemap, robots y JSON-LD.
- `/reservar`: servicio, personas, fecha, hora y lugar → WhatsApp con la solicitud escrita, total estimado y vista previa del mensaje. Funciona sin JavaScript.
- Botones de WhatsApp por tema (`/wa?t=novias`, `curso-auto`, `bono`, `guia`…) con mensajes en `app/Support/WhatsApp.php`; el clic se cuenta de forma anónima.
- CSP con nonce, CSRF, cabeceras de seguridad, `.htaccess` de defensa en profundidad, respaldos cifrados de la BD.
- Precios y artículos del diario desde la BD (tablas `services` y `posts`).

## Créditos

Diseño, desarrollo y SEO: **ClikRank** · Carlos Gómez. Marca y contenido: **Make Up by Fletcher** · María Paula Fletcher.
