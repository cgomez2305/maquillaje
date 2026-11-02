# Make Up by Fletcher · maquillajebucaramanga.com

Sitio web, reservas, tienda con Wompi y panel de control de **Make Up by Fletcher**, en **PHP 8.3 puro + HTML + CSS + JS** (sin WordPress ni frameworks).

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
php cron/crear-usuario.php --email=tu@correo --nombre="Tu nombre" --rol=owner
php -S 127.0.0.1:8000 -t public tools/dev-router.php
```

- Sitio: http://127.0.0.1:8000 · Panel: http://127.0.0.1:8000/panel (la dueña y el socio configuran el 2FA en el primer acceso).
- Datos de demostración (solo desarrollo): `php database/seeds/demo.php`.
- Pruebas del dominio contra la BD local: `php tests/run.php` (o `composer test`).
- Imágenes: las variantes AVIF/WebP/JPG ya están en `public/assets/img`. Para regenerarlas: `pip install pillow pillow-avif-plugin && python3 tools/build-images.py`.

## Estructura

```
public/        único directorio web (index.php, .htaccess, assets/)
app/           Kernel, Router, Http/, Controllers/{Site,Panel,Webhooks}, Domain/, Repositories/, Views/, Support/
config/        app.php (negocio), routes.php, seo.php, catalog.php — sin secretos
database/      migrations/*.sql (con triggers), seeds/
storage/       uploads/ (comprobantes), logs/, cache/, backups/ — fuera de la web
cron/          migrate, crear-usuario, recordatorios, encuestas, alertas, cierre-periodo, sitemap, respaldo, retención
tests/         pruebas del dominio (php tests/run.php)
tools/         utilidades de desarrollo (router local, imágenes, capturas)
```

## Qué incluye

| Fase | Entregable |
|---|---|
| 1 | Front controller, router, CSP con nonce, sesión endurecida, CSRF, límites de peticiones, design tokens, componentes C01–C18, cabecera de vidrio, menú móvil accesible, pie |
| 2 | Inicio, Novias, Servicios y precios, Cursos, Portafolio (filtro + visor), Estudio (cotización), Tienda, Diario y artículo, legales, sitemap y robots, JSON-LD |
| 3 | `/reservar` en 3 pasos sin JS obligatorio, disponibilidad con festivos de Colombia, código `MBF-AAMM-NNNN`, retención de 48 h, historial con cadena de hashes, correos |
| 4 | Panel con login + 2FA TOTP obligatorio, roles en servidor, resumen, reservas, calendario, clientas (enmascaradas para el socio) |
| 5 | Atribución web (6 meses por teléfono), comisiones por línea, cortes con doble aprobación y hash, alertas al socio, encuestas |
| 6 | Tienda y cursos con Wompi: firma de integridad, webhook firmado e idempotente, bonos `BONO-XXXX-XXXX` |
| 7 | Editor del Diario con saneado HTML por lista blanca |
| 8 | Cabeceras de seguridad, `.htaccess` de defensa en profundidad, respaldos cifrados, cron |

## Créditos

Diseño, desarrollo y SEO: **ClikRank** · Carlos Gómez. Marca y contenido: **Make Up by Fletcher** · María Paula Fletcher.
