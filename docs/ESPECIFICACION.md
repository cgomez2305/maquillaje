# Make Up by Fletcher · maquillajebucaramanga.com

Sitio web, sistema de reservas, tienda y panel de control para **Make Up by Fletcher (MUBF)**, la marca de maquillaje profesional de **María Paula Fletcher** en Bucaramanga y Floridablanca (Santander, Colombia).

Este documento es la especificación completa del proyecto. Está escrito para que Claude Code (o cualquier desarrollador) construya el sitio en **PHP puro + HTML + CSS + JavaScript**, sin WordPress ni frameworks, a partir de la maqueta que acompaña este archivo.

---

## 0. Cómo usar este paquete

```
entrega/
├── README.md                 ← este documento (fuente de verdad)
├── maqueta/
│   ├── capturas/             ← JPG de página completa: cada vista en escritorio (1440) y móvil (390),
│   │                           menú móvil abierto, pasos 2 a 4 de la reserva y menú a 360, 375 y 390 px
│   └── fuente/               ← archivos .dc.html de la maqueta + canvas.json (referencia de marcado y estilos)
└── recursos/
    ├── marca/                ← logos, isotipo salmón, nude y negativo (header) en PNG con transparencia
    ├── fotos/                ← fotografías usadas en la maqueta (catálogo 2026 e Instagram)
    ├── fuentes/              ← DM Sans y Comfortaa (respaldo). Aristotelica no se incluye (licencia)
    └── catalogo-fletcher-2026.pdf
```

Equivalencias de nombres entre maqueta y producto: `Main` = Inicio, `Blog` = Diario, `Articulo` = artículo del diario, `Panel-*` = panel privado, `Movil-*` = las mismas páginas vistas a 390 × 844, `Componentes` = sistema de componentes (no es una página pública).

Instrucciones para Claude Code:

1. Lee este README completo antes de escribir código.
2. Usa las **capturas** como referencia visual exacta y los **.dc.html** como referencia de estructura, tokens, espaciados y textos. Los `.dc.html` usan un runtime propio del lienzo de diseño (`<x-dc>`, `{{…}}`, `<sc-for>`, `<sc-if>`): **no se copian tal cual**, se traducen a plantillas PHP.
3. Todos los textos, precios y datos del negocio de este documento son reales (catálogo 2026). Lo que aparece entre corchetes `[…]` es un dato pendiente: déjalo como marcador visible, no lo inventes.
4. Los datos del panel en la maqueta son **de ejemplo**. En producción se leen de la base de datos.
5. Construye por fases (sección 17) y verifica cada criterio de aceptación antes de avanzar.

---

## 1. Contexto del negocio

| Dato | Valor |
|---|---|
| Marca | Make Up by Fletcher (MUBF) · «Fletcher · Make up artist» |
| Dueña | María Paula Fletcher (maquilladora, creadora de MUBF) |
| Estudio | Carrera 29 #158-08, Floridablanca, Santander |
| WhatsApp | +57 316 191 0936 · enlace: `https://wa.me/message/H3JAJC64G4RQM1` |
| Correo | makeupbyfletcher@gmail.com |
| Redes | Instagram, TikTok y Facebook: @makeupbyfletcher / «Make up by Fletcher» |
| Manager | Santiago Turizo · +57 315 597 5501 (no se publica en la web salvo indicación) |
| Dominio | maquillajebucaramanga.com (propiedad de Carlos Gómez, ClikRank) |
| Posicionamiento | Maquillaje profesional con acabado natural y duradero para novias y quinceañeras |
| Zona | Bucaramanga, Floridablanca, Piedecuesta y Girón (a domicilio) |

### 1.1 Servicios (catálogo 2026)

| Código | Nombre | Precio (COP) | Incluye |
|---|---|---|---|
| `pack1` | Pack 1 · Servicio base (maquillaje social) | 200.000 | Asesoría previa (análisis de rasgos), maquillaje social, pestañas postizas de un solo uso |
| `pack2` | Pack 2 · Full experience a domicilio | 250.000 | Asesoría previa; sesión de relajación y preparación de piel (mascarilla húmeda, masaje facial, esencias aromáticas, masaje de manos con cera tibia); música relajante y ventilador de mano; maquillaje en silla profesional con luz de estudio; pestañas postizas; kit de retoque (papel de arroz, brillo labial, espejo de bolsillo) |
| `pack3` | Pack 3 · Novias y quinceañeras | 600.000 | Todo lo del Pack 2 + prueba de maquillaje días antes con 2 propuestas, maquillaje el día del evento, video reel de la preparación, extra fijación |

### 1.2 Cursos (con pago en línea)

| Código | Curso | Precio (COP) | Formato | Contenido |
|---|---|---|---|---|
| `curso-auto` | Automaquillaje | 400.000 | 5 módulos de 1 a 1,5 h | Teoría básica, piel, ojos día, ojos noche, ojos glam, cejas, labios |
| `curso-pro` | Maquillaje profesional | 1.500.000 | 7 módulos de 2 a 3 h | Teoría básica, piel, social día/noche/glam, novias, quinceañeras, editorial, sesión de fotos, cejas, labios. **Requiere modelo de práctica** |

Los cursos se dictan en el estudio de Floridablanca. La alumna recibe lista de materiales con descuento en una tienda aliada o puede empezar con el kit de María Paula.

### 1.3 Políticas de reserva (catálogo)

- Agendar con **mínimo 2 días de anticipación**.
- Datos que se piden: nombre, teléfono, correo, dirección, fecha de cumpleaños.
- **Abono del 50 %** del valor total por Nequi o Bancolombia; el comprobante se envía por WhatsApp.
- El abono **no es reembolsable**; si la clienta no puede asistir se **reagenda**.
- Avisar cambios con **mínimo 1 día** de anticipación; si no, hay penalización.
- Los datos bancarios **no se publican en la web**: se envían en el correo de confirmación y por WhatsApp.

### 1.4 Relación comercial que el sistema debe soportar

Carlos Gómez (ClikRank) crea la web, la posiciona en Google y cobra una **comisión sobre las reservas y ventas que llegan por la web**. El panel debe dar a ambas partes un registro confiable, verificable y difícil de alterar. Ver secciones 9 y 10.

---

## 2. Objetivos

1. **SEO local**: top 3 en Google y Maps para las búsquedas de servicio en Bucaramanga y Floridablanca (ver 12.1).
2. **Conversión**: toda intención de compra termina en una **reserva registrada** (formulario) o en un **WhatsApp identificable** (código WEB).
3. **Control y transparencia**: cada reserva y venta atribuida a la web queda registrada con historial inmutable, y la comisión se calcula sola.
4. **Marca**: diseño limpio y editorial (referencias: Dior Beauty, Prada, Sephora) con la identidad de Fletcher.

---

## 3. Alcance

### 3.1 Sitio público

| Ruta | Vista de la maqueta | Notas |
|---|---|---|
| `/` | `Main.dc.html` · 01 Inicio | H1: «Maquillaje profesional en Bucaramanga» |
| `/maquillaje-de-novia-bucaramanga` | `Novias.dc.html` | Novias y quinceañeras, Pack 3 |
| `/precios-maquillaje-bucaramanga` | `Servicios.dc.html` | Packs, comparativa, grados, domicilio |
| `/cursos-de-maquillaje-bucaramanga` | `Cursos.dc.html` | Cursos con compra en línea |
| `/portafolio` | `Portafolio.dc.html` | Filtro por categoría y visor ampliado |
| `/estudio-maquillaje-floridablanca` | `Estudio.dc.html` | Dirección, mapa, formulario de cotización |
| `/tienda` | `Tienda.dc.html` | Bonos de regalo, kits, productos |
| `/tienda/{slug}` | (derivada de Tienda) | Ficha de producto con pago Wompi |
| `/diario` | `Blog.dc.html` | Blog educativo, filtro por tema |
| `/diario/{slug}` | `Articulo.dc.html` | Plantilla de artículo |
| `/reservar` | `Reservar.dc.html` | Flujo de reserva en 3 pasos + confirmación |
| `/reserva/{codigo}?t={token}` | (confirmación de Reservar) | Estado de la reserva para la clienta |
| `/encuesta/{token}` | (nueva, mismo estilo) | Encuesta post-servicio |
| `/politica-de-privacidad` | (nueva, plantilla de texto) | Ley 1581 de 2012 |
| `/terminos` | (nueva) | Política de reservas y abonos |
| `/sitemap.xml`, `/robots.txt` | — | Generados |

Mesas móviles de la maqueta (`Movil-*.dc.html`): muestran cada vista a 390 × 844. No son páginas separadas; son el mismo HTML responsive.

### 3.2 Panel privado (`/panel`)

| Ruta | Vista | Rol |
|---|---|---|
| `/panel/login` | (nueva, estilo panel) | Todos · usuario + contraseña + 2FA |
| `/panel` | `Panel-Resumen.dc.html` | Dueña y socio |
| `/panel/reservas` | `Panel-Reservas.dc.html` | Dueña (edita) · socio (solo lectura) |
| `/panel/reservas/{codigo}` | Detalle lateral de Panel-Reservas | Ídem |
| `/panel/calendario` | (nueva, misma estética) | Dueña |
| `/panel/clientas` | (nueva) | Dueña · socio ve teléfonos enmascarados |
| `/panel/ventas` | (nueva) | Pedidos en línea de cursos, bonos y productos |
| `/panel/comisiones` | `Panel-Comisiones.dc.html` | Dueña y socio · aprobación de corte |
| `/panel/historial` | (nueva) | Registro de auditoría |
| `/panel/ajustes` | (nueva) | Solo dueña: horarios, días bloqueados, precios. Tasas de comisión: requieren aprobación de ambos |

### 3.3 Fuera de alcance de la v1

- Pasarela para servicios de maquillaje (el abono sigue por transferencia).
- API oficial de WhatsApp (se usa clic para chatear con mensaje prellenado). Se deja preparada la interfaz para una v2.
- App móvil.

---

## 4. Stack y principios técnicos

| Capa | Decisión | Motivo |
|---|---|---|
| Lenguaje | **PHP 8.3** (mínimo 8.2), `declare(strict_types=1)` | Hosting compartido Hostinger, sin dependencias pesadas |
| Arquitectura | Front controller + router propio + controladores + vistas PHP | Sin framework, código auditable |
| Base de datos | **MySQL 8 / MariaDB 10.6+**, `utf8mb4_unicode_ci`, motor InnoDB | Disponible en Hostinger |
| Acceso a datos | **PDO** con sentencias preparadas, `ATTR_EMULATE_PREPARES = false`, `ERRMODE_EXCEPTION` | Seguridad ante inyección SQL |
| CSS | **CSS propio con variables (design tokens)**, sin Tailwind | La maqueta ya está construida con tokens; evita paso de compilación en hosting compartido y mantiene el CSS pequeño. Si se prefiere Tailwind, usar el CLI compilado en local y nunca el CDN |
| JS | Vanilla ES2020 en módulos + **GSAP 3 + ScrollTrigger** (servidos desde `/public/assets/vendor`, no CDN) | Animaciones de scroll y parallax; GSAP es gratuito desde 2025 |
| Correo | **PHPMailer** por SMTP (correo de Hostinger) | Envío fiable con SPF/DKIM |
| Dependencias | Composer **solo** para: `phpmailer/phpmailer`. Opcionales: `vlucas/phpdotenv`, una librería TOTP mantenida. Versiones fijadas en `composer.lock` | Superficie mínima |
| Pagos | **Wompi** (Bancolombia) para cursos, bonos y productos | Pasarela colombiana, PSE, Nequi y tarjetas |
| Servidor | Apache (Hostinger) con `DocumentRoot` apuntando a `/public` | Nada fuera de `/public` es accesible por URL |

Principios:

- **Nada de lógica en las vistas** salvo bucles y escape.
- **Toda salida se escapa** con un helper `e()` (`htmlspecialchars` con `ENT_QUOTES | ENT_SUBSTITUTE`, `UTF-8`).
- **Toda entrada se valida en el servidor** con listas blancas.
- **Fechas en UTC en la base de datos**, mostradas en `America/Bogota`.
- **Dinero en enteros** (pesos colombianos sin decimales, columnas `INT UNSIGNED`). Para Wompi, convertir a centavos al enviar.
- **Mobile first** y accesibilidad AA.

---

## 5. Estructura de carpetas

```
/
├── public/                     ← único directorio público (DocumentRoot)
│   ├── index.php               ← front controller
│   ├── .htaccess               ← reescritura a index.php, cabeceras, bloqueo de archivos ocultos
│   ├── assets/
│   │   ├── css/  (tokens.css, base.css, components.css, pages/*.css, panel.css)
│   │   ├── js/   (app.js, reservar.js, portafolio.js, panel.js)
│   │   ├── vendor/ (gsap.min.js, ScrollTrigger.min.js)
│   │   ├── fonts/  (woff2 autoalojadas)
│   │   └── img/    (marca/, fotos/ en AVIF, WebP y JPG con varias anchuras)
│   └── robots.txt
├── app/
│   ├── bootstrap.php           ← carga config, sesión, cabeceras, autoload
│   ├── Router.php
│   ├── Http/ (Request.php, Response.php, Csrf.php, RateLimiter.php, SecurityHeaders.php)
│   ├── Controllers/
│   │   ├── Site/   (Home, Novias, Servicios, Cursos, Portafolio, Estudio, Tienda, Diario, Reservar, Encuesta, Legal, Sitemap)
│   │   ├── Panel/  (Auth, Dashboard, Reservas, Calendario, Clientas, Ventas, Comisiones, Historial, Ajustes, Archivos)
│   │   └── Webhooks/ (WompiController.php)
│   ├── Domain/
│   │   ├── Booking/ (BookingService, BookingStateMachine, BookingCode)
│   │   ├── Attribution/ (AttributionService, Source enum)
│   │   ├── Commission/ (CommissionCalculator, PeriodService)
│   │   ├── Orders/ (OrderService, WompiClient, WompiSignature)
│   │   ├── Notifications/ (Mailer, Templates)
│   │   └── Audit/ (AuditLog)
│   ├── Repositories/           ← una clase por tabla, solo PDO preparado
│   ├── Views/
│   │   ├── layouts/ (site.php, panel.php)
│   │   ├── partials/ (header, menu-movil, footer, fab-whatsapp, cta, faq, card-servicio, card-articulo, schema/*.php)
│   │   ├── site/ (una vista por página)
│   │   ├── panel/
│   │   └── emails/
│   └── Support/ (helpers.php: e(), money(), phone_e164(), url(), asset() con hash de versión)
├── config/ (app.php, routes.php, services.php, seo.php)  ← sin secretos
├── database/
│   ├── migrations/ (001_init.sql, 002_seed_services.sql, …)
│   └── seeds/
├── storage/                    ← fuera de public, sin acceso web
│   ├── uploads/comprobantes/
│   ├── logs/
│   ├── cache/
│   └── backups/
├── cron/ (recordatorios.php, encuestas.php, alertas.php, cierre-periodo.php, sitemap.php)
├── tests/                      ← PHPUnit opcional para Domain/
├── .env.example                ← plantilla sin valores reales
├── composer.json
└── README.md
```

---

## 6. Diseño (fuente: maqueta y `Componentes.dc.html`)

### 6.1 Tokens

```css
:root {
  /* Color */
  --porcelana: #FBF8F5;   /* fondo principal */
  --arena:     #F2EAE3;   /* bloques secundarios */
  --tinta:     #171312;   /* texto, botones, secciones oscuras */
  --salmon:    #F98F7D;   /* color de marca: decorativo, nunca para texto (contraste insuficiente) */
  --terracota: #A5462F;   /* etiquetas, hover, énfasis de texto */
  --cacao:     #4F4540;   /* párrafos */
  --topo:      #6E625C;   /* datos secundarios */
  --linea:     rgba(23,19,18,.12);

  /* Tipografía */
  --font-display: 'Aristotelica Display', 'Comfortaa', sans-serif; /* peso 200 (ExtraLight) */
  --font-body: 'DM Sans', system-ui, sans-serif;

  /* Espaciado (escala) */
  --s-1: 8px; --s-2: 16px; --s-3: 28px; --s-4: 40px; --s-5: 72px; --s-6: 96px;
  --section-y: 128px;            /* 80px en móvil */
  --container: 1320px;
  --gutter: 40px;                /* 20px en móvil */

  /* Radios */
  --r-foto: 20px; --r-pack: 28px; --r-cta: 40px; --r-pill: 999px;
  --r-arco: 999px 999px 28px 28px;

  /* Movimiento */
  --ease: cubic-bezier(.16, 1, .3, 1);
}
```

**Mesh gradient de marca** (solo bloques de llamada salmón): ver la clase `.mesh` en `Main.dc.html`. Variante oscura `.mesh-dark` para bloques en tinta.

### 6.2 Tipografía

| Nivel | Fuente | Tamaño | Interlineado | Tracking |
|---|---|---|---|---|
| Display XL (H1) | Aristotelica Display ExtraLight | clamp(56px, 7.4vw, 116px); móvil 48–64px | 1.02 | −3,5 % |
| Display L (H2) | Ídem | clamp(44px, 5vw, 76px) | 1.02–1.1 | −3,5 % |
| Display M (tarjetas) | Ídem | 28–40px | 1.1 | −3 % |
| Etiqueta | DM Sans 500 | 12px, mayúsculas | — | +20 % |
| Cuerpo | DM Sans 400 | 17–18px | 1.65–1.75 | 0 |
| Pequeño | DM Sans 400 | 13–15px | 1.55 | 0 |

- **Aristotelica Display** es comercial: la licencia web debe comprarse (o usar la versión que tenga la marca) y autoalojarse en WOFF2 con `font-display: swap`. Mientras tanto, el respaldo es **Comfortaa 300**.
- Párrafos siempre en **DM Sans** (autoalojada, variable).
- Un solo H1 por página, que contenga la keyword principal (ver 12.2).

### 6.3 Componentes

Los 18 componentes están documentados con su vista en vivo en `Componentes.dc.html`. Cada uno es un parcial PHP en `app/Views/partials/`:

| ID | Componente | Notas de implementación |
|---|---|---|
| C01 | Botones (primario, fantasma, claro, salmón) | `<a class="btn">` o `<button>`. Presión: `transform: scale(.97)` en 100 ms. Altura mínima 44–50px |
| C02 | Encabezado de vidrio | `position: sticky`, `backdrop-filter: saturate(180%) blur(20px)`, fondo porcelana al 74 %. Respaldo sólido con `prefers-reduced-transparency` |
| C03 | Menú móvil | Hoja a pantalla completa (`100dvh`), sin scroll; tamaño de enlaces con `clamp(28px, 4.6vh, 38px)`. Bloquear el scroll del body al abrir, cerrar con Escape y devolver el foco al botón. `aria-expanded`, `role="dialog"` y `aria-modal` |
| C04 | Arco de imagen y sello giratorio | `border-radius: var(--r-arco)`. Sello: SVG con `textPath`, rotación 24s, detenida con movimiento reducido |
| C05 | Tarjeta de servicio | Foto 4:5, etiqueta de vidrio, título, precio, descripción, línea inferior |
| C06 | Tarjeta de pack | Lista de incluidos con check; variante destacada en tinta |
| C07 | Fila de precio | Nombre y detalle a la izquierda, precio a la derecha, separadores de 1px |
| C08 | Paso numerado | Cuadrícula de 4 columnas (2 en móvil) |
| C09 | Acordeón de preguntas | `<details>/<summary>` + JSON-LD FAQPage |
| C10 | Chips de filtro | `<button aria-pressed>`; filtrado en el cliente para portafolio y diario |
| C11 | Tarjeta de artículo | Imagen 4:5, categoría, título, extracto, tiempo de lectura |
| C12 | Bloque de llamada | Variantes `.mesh` (salmón) y `.mesh-dark` |
| C13 | Campo de formulario | Línea inferior, etiqueta en mayúsculas, error debajo con `aria-describedby` |
| C14 | Botón flotante de WhatsApp | Fijo abajo a la derecha; en móvil solo el icono |
| C15 | Marquesina | Texto duplicado, animación CSS, `aria-hidden` |
| C16 | Reseña | Solo reseñas reales copiadas de Google Business Profile |
| C17 | Barra de lectura y tabla de contenido | Artículo del diario |
| C18 | Pie de página | 4 columnas: marca, servicios, estudio, zonas (enlazado interno SEO) |

### 6.4 Responsive

- Puntos de corte: **1180px** (navegación pasa a botón de menú; el artículo oculta la tabla de contenido) y **960px** (todas las cuadrículas a una columna, márgenes de 20px, secciones de 80px).
- Ninguna sección puede producir scroll horizontal: verificar a 360, 390 y 768px.
- Las cuadrículas de dos columnas definen su columna en CSS (no en atributos `style`) para que el punto de corte las pueda apilar.

### 6.5 Movimiento

- Entradas al hacer scroll: subir 56px y aparecer (GSAP ScrollTrigger, `start: "top 85%"`).
- Parallax en fotos: escala 1.14 y recorrido de ±7 % (`scrub: true`).
- Cabecera de cada página: entrada escalonada de 80 ms.
- Todo se desactiva con `prefers-reduced-motion: reduce`. El contenido debe verse completo sin JavaScript.

### 6.6 Imágenes

- Fotos actuales: catálogo 2026 (alta resolución) e Instagram (640px, ampliadas). **Pedir originales en alta** para reemplazar las de Instagram.
- Exportar en AVIF + WebP + JPG con `srcset` (480, 768, 1200, 1600) y `sizes`. `loading="lazy"` salvo la imagen del hero (`fetchpriority="high"`).
- Todo `alt` descriptivo en español. Decorativas con `alt=""`.
- No usar la foto de la novia con expresión de video humorístico en lugares destacados.

---

## 7. Comportamiento de las vistas públicas

- **Cabecera**: logo (isotipo negativo + «Fletcher»), navegación (Novias, Servicios y precios, Cursos, Portafolio, Tienda, Diario, Estudio) y botón **«Reservar fecha» → `/reservar`**.
- **Botón flotante**: WhatsApp con mensaje prellenado que incluye el código de origen (ver 9.3).
- **Portafolio**: filtro por categoría (Todo, Novias, Social, Editorial, Detrás de cámaras) y visor ampliado con cierre por botón, Escape y clic fuera; foco atrapado dentro del visor.
- **Diario**: listado con filtro por tema, artículo destacado, guía gratuita para novias (captura de correo y fecha de boda, con doble opt-in).
- **Estudio**: formulario de cotización que guarda un lead en la base de datos **y luego** abre WhatsApp con el mensaje armado y el código del lead.
- **Tienda**: bonos de regalo (Pack 1, Pack 2, curso de automaquillaje), kits y productos. Compra con Wompi (sección 11).

### 7.1 Flujo de reserva (`/reservar`)

1. **Servicio**: Pack 1, 2 o 3 y número de personas (1, 2, 3, 4 o más).
2. **Fecha y lugar**: calendario mensual con días bloqueados (ocupados, festivos, bloqueos manuales) y mínimo 2 días de anticipación; horas disponibles; estudio o a domicilio (domicilio fuerza Pack 2 o 3 o pide dirección).
3. **Datos**: nombre, WhatsApp, correo, fecha de cumpleaños, dirección (si es a domicilio), tipo de evento, cómo nos conoció, nota. Casilla obligatoria de autorización de datos (Ley 1581) y casilla opcional de novedades.
4. **Confirmación**: código `MBF-AAMM-NNNN`, resumen, valor total, abono del 50 %, botón «Enviar comprobante por WhatsApp» con mensaje prellenado que incluye el código.

El resumen lateral (sticky en escritorio, debajo en móvil) se actualiza en cada paso. El formulario funciona sin JavaScript (cada paso es un POST) y se mejora con JS.

Al enviar:

- Se crea la clienta (o se reutiliza por teléfono normalizado E.164) y la reserva en estado `solicitada`.
- Se registra el origen (sección 9.3) y el evento `creada` en el historial.
- Se envía correo a la clienta (código, resumen, datos de pago del abono, enlace de estado) y a la dueña.
- Disponibilidad: el horario queda **retenido 48 h** hasta que se confirme el abono; si no se confirma, pasa a `vencida` y libera el cupo (cron).

---

## 8. Base de datos

Migración inicial orientativa (MySQL 8). Todas las tablas con `created_at`/`updated_at` en UTC salvo las de solo inserción.

```sql
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,          -- password_hash(PASSWORD_ARGON2ID)
  role ENUM('owner','partner','staff') NOT NULL, -- dueña, socio (solo lectura + aprobación), asistente
  totp_secret VARBINARY(64) NULL,               -- cifrado con APP_KEY (sodium_crypto_secretbox)
  totp_enabled TINYINT(1) NOT NULL DEFAULT 0,
  failed_logins SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL
);

CREATE TABLE clients (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  phone_e164 VARCHAR(20) NOT NULL UNIQUE,       -- identidad de la clienta (+573001234567)
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NULL,
  birthday DATE NULL,
  address VARCHAR(255) NULL,
  first_source ENUM('web_form','whatsapp_web','google_business','instagram','tiktok','referral','manual_other') NOT NULL,
  first_seen_at DATETIME NOT NULL,              -- inicio de la ventana de atribución
  web_attributed_until DATETIME NULL,           -- first_seen_at + 6 meses si el primer contacto fue web
  utm JSON NULL,                                -- utm_source, utm_medium, utm_campaign, gclid, referrer
  consent_data_at DATETIME NOT NULL,            -- autorización Ley 1581
  consent_marketing_at DATETIME NULL,
  created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL
);

CREATE TABLE services (
  id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,             -- pack1, pack2, pack3
  name VARCHAR(120) NOT NULL,
  price INT UNSIGNED NOT NULL,                  -- COP
  category ENUM('service','course','product','gift') NOT NULL,
  active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE commission_rates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category ENUM('service','course','product','gift') NOT NULL,
  rate_bp SMALLINT UNSIGNED NOT NULL,           -- puntos básicos: 1000 = 10 %
  valid_from DATETIME NOT NULL,
  approved_by_owner_at DATETIME NULL,
  approved_by_partner_at DATETIME NULL          -- una tasa solo rige con ambas aprobaciones
);

CREATE TABLE bookings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code CHAR(13) NOT NULL UNIQUE,                -- MBF-2611-0142
  public_token CHAR(43) NOT NULL UNIQUE,        -- base64url de 32 bytes aleatorios, para /reserva/{code}?t=
  client_id INT UNSIGNED NOT NULL,
  service_id SMALLINT UNSIGNED NOT NULL,
  people TINYINT UNSIGNED NOT NULL DEFAULT 1,
  starts_at DATETIME NOT NULL,
  location ENUM('studio','home') NOT NULL,
  address VARCHAR(255) NULL,
  event_type VARCHAR(40) NULL,
  notes TEXT NULL,
  unit_price INT UNSIGNED NOT NULL,             -- copia del precio al crear
  total INT UNSIGNED NOT NULL,
  deposit_amount INT UNSIGNED NOT NULL,         -- 50 %
  deposit_confirmed_at DATETIME NULL,
  status ENUM('solicitada','confirmada','realizada','cancelada','no_asistio','vencida','reprogramada') NOT NULL,
  source ENUM('web_form','whatsapp_web','google_business','web_returning','manual') NOT NULL,
  is_web_attributed TINYINT(1) NOT NULL,        -- calculado al crear, inmutable
  commission_rate_bp SMALLINT UNSIGNED NOT NULL,-- copia de la tasa vigente
  created_by INT UNSIGNED NULL,                 -- NULL = la clienta desde la web
  created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
  FOREIGN KEY (client_id) REFERENCES clients(id),
  FOREIGN KEY (service_id) REFERENCES services(id),
  INDEX (starts_at), INDEX (status), INDEX (is_web_attributed)
);

-- Historial inmutable: solo INSERT. El usuario de BD de la app no tiene UPDATE ni DELETE sobre esta tabla.
CREATE TABLE booking_events (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NOT NULL,
  type VARCHAR(40) NOT NULL,                    -- creada, abono_confirmado, reprogramada, realizada, cancelada, valor_modificado, correo_enviado, encuesta_respondida…
  from_status VARCHAR(20) NULL, to_status VARCHAR(20) NULL,
  reason VARCHAR(500) NULL,                     -- obligatorio en cancelación, no asistió y cambio de valor
  payload JSON NULL,                            -- valores antes y después
  actor_user_id INT UNSIGNED NULL,              -- NULL = sistema o clienta
  ip VARBINARY(16) NULL, user_agent VARCHAR(255) NULL,
  prev_hash CHAR(64) NULL, hash CHAR(64) NOT NULL, -- cadena de hashes SHA-256 para detectar alteraciones
  created_at DATETIME NOT NULL,
  FOREIGN KEY (booking_id) REFERENCES bookings(id),
  INDEX (booking_id, created_at)
);

CREATE TABLE booking_files (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NOT NULL,
  kind ENUM('comprobante_abono','otro') NOT NULL,
  stored_name CHAR(64) NOT NULL,                -- nombre aleatorio en storage/uploads
  mime VARCHAR(50) NOT NULL, size_bytes INT UNSIGNED NOT NULL, sha256 CHAR(64) NOT NULL,
  uploaded_by INT UNSIGNED NOT NULL, created_at DATETIME NOT NULL
);

CREATE TABLE surveys (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  booking_id INT UNSIGNED NOT NULL UNIQUE,
  token CHAR(43) NOT NULL UNIQUE,
  sent_at DATETIME NULL, answered_at DATETIME NULL,
  rating TINYINT UNSIGNED NULL, attended TINYINT(1) NULL, comment TEXT NULL
);

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(120) NOT NULL UNIQUE, name VARCHAR(160) NOT NULL,
  category ENUM('course','product','gift') NOT NULL,
  price INT UNSIGNED NOT NULL, stock INT NULL, active TINYINT(1) NOT NULL DEFAULT 1,
  description TEXT NULL, image VARCHAR(255) NULL
);

CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  reference CHAR(20) NOT NULL UNIQUE,           -- PED-AAMM-NNNN, se envía a Wompi
  client_id INT UNSIGNED NOT NULL,
  total INT UNSIGNED NOT NULL,
  status ENUM('pendiente','aprobada','rechazada','anulada','reembolsada') NOT NULL,
  wompi_transaction_id VARCHAR(64) NULL,
  commission_rate_bp SMALLINT UNSIGNED NOT NULL,
  is_web_attributed TINYINT(1) NOT NULL DEFAULT 1,
  paid_at DATETIME NULL,
  created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL
);

CREATE TABLE order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL, product_id INT UNSIGNED NOT NULL,
  qty SMALLINT UNSIGNED NOT NULL, unit_price INT UNSIGNED NOT NULL,
  gift_recipient VARCHAR(120) NULL               -- bonos de regalo
);

CREATE TABLE payment_events (                    -- webhooks Wompi, solo INSERT
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  provider VARCHAR(20) NOT NULL, event_id VARCHAR(80) NOT NULL UNIQUE, -- idempotencia
  order_reference CHAR(20) NULL, status VARCHAR(30) NOT NULL,
  raw JSON NOT NULL, signature_valid TINYINT(1) NOT NULL, received_at DATETIME NOT NULL
);

CREATE TABLE commission_periods (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  year SMALLINT UNSIGNED NOT NULL, month TINYINT UNSIGNED NOT NULL,
  status ENUM('abierto','en_aprobacion','cerrado','pagado') NOT NULL,
  total_base INT UNSIGNED NOT NULL DEFAULT 0, total_commission INT UNSIGNED NOT NULL DEFAULT 0,
  owner_approved_at DATETIME NULL, partner_approved_at DATETIME NULL,
  paid_at DATETIME NULL, payment_note VARCHAR(255) NULL,
  snapshot_hash CHAR(64) NULL,                  -- hash de las líneas al cerrar
  UNIQUE (year, month)
);

CREATE TABLE commission_lines (                  -- se congelan al cerrar el periodo
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  period_id INT UNSIGNED NOT NULL,
  booking_id INT UNSIGNED NULL, order_id INT UNSIGNED NULL,
  base INT UNSIGNED NOT NULL, rate_bp SMALLINT UNSIGNED NOT NULL, amount INT UNSIGNED NOT NULL,
  note VARCHAR(255) NULL
);

CREATE TABLE leads (                             -- cotizaciones, guía para novias, clics a WhatsApp con código
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code CHAR(13) NOT NULL UNIQUE,                -- WEB-AAMM-NNNN
  kind ENUM('cotizacion','guia_novias','whatsapp_click') NOT NULL,
  name VARCHAR(120) NULL, phone_e164 VARCHAR(20) NULL, email VARCHAR(190) NULL,
  payload JSON NULL, utm JSON NULL, created_at DATETIME NOT NULL
);

CREATE TABLE blocked_slots (                     -- días u horas no disponibles
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  starts_at DATETIME NOT NULL, ends_at DATETIME NOT NULL, reason VARCHAR(120) NULL
);

CREATE TABLE posts (                             -- diario
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(160) NOT NULL UNIQUE, title VARCHAR(200) NOT NULL,
  meta_description VARCHAR(160) NOT NULL, category VARCHAR(40) NOT NULL,
  target_keyword VARCHAR(120) NOT NULL, excerpt VARCHAR(300) NOT NULL,
  body_html MEDIUMTEXT NOT NULL,                -- saneado al guardar (lista blanca de etiquetas)
  faq JSON NULL, cover VARCHAR(255) NULL, reading_minutes TINYINT UNSIGNED NOT NULL,
  status ENUM('borrador','publicado') NOT NULL, published_at DATETIME NULL, updated_at DATETIME NOT NULL
);

CREATE TABLE audit_log (                         -- acciones del panel fuera de reservas: login, ajustes, tasas, exportaciones
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL, action VARCHAR(60) NOT NULL, target VARCHAR(80) NULL,
  payload JSON NULL, ip VARBINARY(16) NULL, created_at DATETIME NOT NULL
);

CREATE TABLE rate_limits (
  bucket VARCHAR(120) PRIMARY KEY, hits INT UNSIGNED NOT NULL, reset_at DATETIME NOT NULL
);
```

Usuarios de base de datos: `app_rw` (lectura y escritura, **sin** UPDATE ni DELETE en `booking_events`, `payment_events` y `audit_log`) y un usuario separado para migraciones. Las líneas de comisión de periodos cerrados se protegen con triggers `BEFORE UPDATE` y `BEFORE DELETE` que lanzan error si el periodo está `cerrado` o `pagado`. Si el hosting compartido no permite separar permisos por tabla, aplicar triggers equivalentes también a las tablas de solo inserción.

---

## 9. Reglas de negocio

### 9.1 Estados de una reserva

```
solicitada ──abono confirmado──► confirmada ──fecha cumplida──► realizada
    │                                │  │
    │                                │  └──► no_asistio (motivo obligatorio)
    │                                └──► reprogramada ──► confirmada (nueva fecha, mismo código)
    ├──48 h sin abono (cron)──► vencida
    └──► cancelada (motivo obligatorio; también desde confirmada)
```

- Las reservas **nunca se eliminan**. Solo cambian de estado con un evento en `booking_events`.
- Cambiar `total` o `service_id` después de `confirmada` exige motivo y genera alerta al socio.
- `realizada` solo se puede marcar el mismo día o después de `starts_at`.
- El origen (`source`) y `is_web_attributed` **no se pueden editar** desde el panel.

### 9.2 Código de reserva

`MBF-` + año (2 dígitos) + mes + `-` + consecutivo de 4 dígitos por mes (ej. `MBF-2611-0142`). Se genera en una transacción con bloqueo (`SELECT … FOR UPDATE` sobre un contador) para evitar duplicados.

### 9.3 Atribución a la web

Una reserva es **atribuida a la web** (`is_web_attributed = 1`) cuando se cumple cualquiera de estas condiciones:

| Origen (`source`) | Cómo se detecta |
|---|---|
| `web_form` | Creada por la clienta desde `/reservar` o desde la cotización de `/estudio-…` |
| `whatsapp_web` | La dueña la registra desde una conversación que empezó con un código `WEB-…` (los botones de WhatsApp de la web generan un lead con código y lo incluyen en el mensaje prellenado: «Hola, vengo de la web · WEB-2611-0087») |
| `google_business` | Llegó por la ficha de Google (enlace con `?utm_source=gbp` en el botón «Sitio web» y «Reservar» de la ficha) |
| `web_returning` | La dueña registra manualmente una reserva de una clienta cuyo `phone_e164` tiene `web_attributed_until` vigente (6 meses desde su primer registro web) |

- Al registrar una reserva manual, el sistema **busca el teléfono** automáticamente. Si la clienta es atribuible, la marca sola; la dueña no puede desmarcarla.
- Los UTM y el `gclid` se guardan en una cookie de primera parte (30 días, `SameSite=Lax`, `Secure`) y se copian a la clienta al crear su primer registro.
- La ventana de atribución (6 meses) y los orígenes válidos son parámetros en `config/app.php` y deben coincidir con el acuerdo escrito entre las partes.

### 9.4 Cálculo de comisiones

- Cada reserva o pedido guarda `commission_rate_bp` **al crearse** (tasa vigente aprobada por ambos). Cambiar tasas no afecta lo anterior.
- **Servicios**: generan comisión cuando pasan a `realizada`. Base = `total` (valor completo del servicio, no solo el abono).
- **No asistió con abono retenido**: base = abono (parámetro configurable; por defecto sí comisiona).
- **Cancelada o vencida**: no comisiona.
- **Pedidos en línea**: comisionan cuando el webhook de Wompi confirma `APPROVED`. Un reembolso posterior genera una línea negativa en el periodo en que ocurre.
- Fórmula: `amount = intdiv(base * rate_bp, 10000)`.
- Tasas de ejemplo en la maqueta: servicios 10 %, cursos 15 %, tienda y bonos 10 %. **Las reales se definen en el acuerdo** y se cargan con doble aprobación.

### 9.5 Cortes mensuales

1. El día 1 a las 00:10 (cron) se pasa el periodo anterior a `en_aprobacion` y se generan las `commission_lines`.
2. La dueña y el socio revisan y **aprueban** desde `/panel/comisiones`. Cualquiera puede dejar comentarios.
3. Con las dos aprobaciones el periodo queda `cerrado`: se guarda `snapshot_hash` de las líneas y ya no se puede editar.
4. Cambios tardíos (por ejemplo, una reserva de octubre marcada como realizada en noviembre) entran al periodo abierto como ajuste, nunca reabren uno cerrado.
5. La dueña marca el periodo como `pagado` con una nota (fecha y medio de pago).
6. Exportación CSV y PDF del corte.

### 9.6 Verificación independiente (anti-fuga)

El objetivo es que el socio no dependa solo de lo que se marca a mano:

- **Correo a la clienta en cada paso** (confirmación, recordatorio de 24 h, encuesta). Solo existe si la reserva está en el sistema; así la herramienta es útil para la dueña y no hay incentivo para saltarla.
- **Encuesta post-servicio** (24 h después de `starts_at`): «¿Cómo te fue?» con calificación, «¿Recibiste el servicio?» y enlace para dejar reseña en Google. Si la clienta responde que sí asistió y la reserva figura como cancelada o no asistió, se genera una **alerta**.
- **Alertas al socio** (correo inmediato y resumen semanal):
  - cancelación o no asistencia de una reserva atribuida a la web;
  - reserva confirmada cuya fecha pasó hace más de 72 h sin marcarse como realizada;
  - cambio de valor o de servicio después de confirmada;
  - encuesta contradictoria con el estado;
  - nueva clienta con teléfono ya atribuido registrada como origen manual.
- **Historial con cadena de hashes**: cada evento guarda el hash del anterior; una vista de verificación recalcula la cadena y muestra si algo se alteró.
- **Embudo comparado**: el panel cruza los clics a WhatsApp y reservas medidos por analítica con las reservas registradas, para detectar caídas anómalas.

### 9.7 Roles y permisos

| Acción | Dueña (`owner`) | Socio (`partner`) | Asistente (`staff`) |
|---|---|---|---|
| Ver resumen, reservas, comisiones, historial | Sí | Sí (teléfonos y correos enmascarados) | Reservas y calendario |
| Crear y editar reservas, confirmar abonos, subir comprobantes | Sí | No | Sí |
| Cancelar o marcar no asistió | Sí (con motivo) | No | No |
| Ajustes (precios, horarios, bloqueos) | Sí | No | No |
| Proponer y aprobar tasas de comisión | Aprueba | Aprueba | No |
| Aprobar cortes | Sí | Sí | No |
| Ver comprobantes | Sí | Sí | Sí |
| Exportar | Sí | Sí | No |

Todas las rutas del panel verifican el rol en el servidor (middleware), no solo ocultando botones.

---

## 10. Notificaciones

| Momento | Destinatario | Canal | Contenido |
|---|---|---|---|
| Reserva creada | Clienta | Correo | Código, resumen, valor, abono, datos de pago, enlace de estado, botón de WhatsApp con el código |
| Reserva creada | Dueña | Correo | Datos de la reserva y enlace al panel |
| Abono confirmado | Clienta | Correo | Fecha confirmada, política de cambios |
| 24 h antes | Clienta | Correo | Recordatorio, dirección del estudio o confirmación del domicilio |
| 24 h después | Clienta | Correo | Encuesta y enlace a reseña de Google |
| 48 h sin abono | Clienta | Correo | Aviso de liberación del cupo |
| Alertas (9.6) | Socio | Correo inmediato + resumen semanal | Detalle y enlace |
| Día 1 del mes | Dueña y socio | Correo | Corte listo para aprobar |

- Envío por SMTP con SPF, DKIM y DMARC configurados en el dominio.
- WhatsApp: en la v1 solo enlaces `wa.me` con mensaje prellenado. La capa `Notifications` debe tener una interfaz para añadir WhatsApp Cloud API en la v2.
- Los cron que envían correos son idempotentes (marcan `sent_at`).

---

## 11. Pagos en línea (solo cursos, bonos y productos)

- Pasarela: **Wompi** (Widget o Web Checkout). Llaves de prueba en desarrollo y de producción en `.env`.
- Flujo:
  1. La clienta llena sus datos y se crea el pedido `pendiente` con referencia única.
  2. El servidor calcula la **firma de integridad** (`SHA256(referencia + monto_en_centavos + moneda + secreto_de_integridad)`) y la entrega al widget. El monto **siempre** sale del servidor.
  3. La página de retorno **no** confirma nada; solo muestra «estamos verificando tu pago».
  4. El **webhook** `/webhooks/wompi` valida la firma del evento con el secreto de eventos, comprueba que monto y referencia coinciden con el pedido, guarda el evento (idempotente por `event_id`) y actualiza el pedido.
  5. Con `APPROVED`: correo de confirmación (cupo del curso, bono en PDF con código único o datos de envío), línea de comisión al periodo abierto.
- Bonos de regalo: código único (`BONO-XXXX-XXXX`) canjeable en el panel. Al canjearse crea una reserva vinculada que **no** vuelve a comisionar.
- Nunca guardar datos de tarjetas. Toda la captura ocurre en Wompi.

---

## 12. SEO

### 12.1 Keywords (estudio de nicho, octubre de 2026)

| Keyword | Búsquedas/mes | Página destino |
|---|---|---|
| maquillaje bucaramanga | 320–390 | Inicio |
| tienda de maquillaje bucaramanga | 260 | Tienda |
| cursos de maquillaje bucaramanga / academia | 240 (grupo) | Cursos |
| maquillaje floridablanca | 50 | Estudio |
| maquillaje artístico bucaramanga | 40 | Portafolio |
| maquilladora bucaramanga / maquilladoras en bucaramanga | 30 + 30 | Inicio |
| maquillaje profesional bucaramanga | 30 | Inicio |
| maquillaje social | 30 | Servicios |
| maquillaje de novia / para novias bucaramanga | 20 + 10 | Novias |
| maquillaje a domicilio bucaramanga | 10 | Servicios (sección domicilio) |
| maquillaje para quinceañera | 10 | Novias |
| maquillaje para grados (Colombia) | 1.900 (pico 6.600 en noviembre) | Servicios (sección grados) + artículo |
| curso de automaquillaje (Colombia) | 260 | Cursos + artículo |

Competencia débil: el top 10 de «maquilladora bucaramanga» son perfiles de Instagram y directorios, sin webs propias de maquilladoras.

### 12.2 Metadatos por página

| Ruta | `<title>` (≤ 60) | H1 |
|---|---|---|
| `/` | Maquillaje profesional en Bucaramanga · Make Up by Fletcher | Maquillaje profesional en Bucaramanga |
| `/maquillaje-de-novia-bucaramanga` | Maquillaje de novia en Bucaramanga y Floridablanca · Fletcher | Maquillaje de novia en Bucaramanga y Floridablanca |
| `/precios-maquillaje-bucaramanga` | Precios de maquillaje en Bucaramanga 2026 · Fletcher | Precios de maquillaje en Bucaramanga |
| `/cursos-de-maquillaje-bucaramanga` | Cursos de maquillaje en Bucaramanga · Make Up by Fletcher | Cursos de maquillaje en Bucaramanga |
| `/estudio-maquillaje-floridablanca` | Estudio de maquillaje en Floridablanca · Fletcher | Estudio de maquillaje en Floridablanca |
| `/tienda` | Tienda de maquillaje y bonos de regalo · Fletcher | Tienda de maquillaje en Bucaramanga |
| `/portafolio` | Portafolio de maquillaje · Make Up by Fletcher | Portafolio de maquillaje en Bucaramanga |
| `/diario` | Diario de belleza · Blog de maquillaje · Fletcher | Diario de belleza |

En la maqueta, el H1 combina una etiqueta con la keyword y una frase de marca dentro del mismo `<h1>` (por ejemplo: etiqueta «Maquillaje profesional en Bucaramanga» + «Natural. Duradero. Tuyo.»). Mantener esa estructura.

Cada página lleva además: `meta description` (≤ 155 caracteres), `link rel="canonical"`, Open Graph y Twitter Card, breadcrumbs visibles y en JSON-LD.

### 12.3 Datos estructurados (JSON-LD)

- Sitio: `BeautySalon` (subtipo de `LocalBusiness`) con nombre, URL, logo, teléfono, dirección (Carrera 29 #158-08, Floridablanca, Santander, CO), `geo` [coordenadas pendientes], `areaServed` (Bucaramanga, Floridablanca, Piedecuesta, Girón), `priceRange`, `openingHoursSpecification` [horario pendiente], `sameAs` (Instagram, TikTok, Facebook).
- Servicios: `Service` u `Offer` por pack, con precio en COP.
- Cursos: `Course` con `provider` y `offers`.
- Productos y bonos: `Product` + `Offer`.
- Preguntas frecuentes: `FAQPage` en Inicio, Novias, Cursos y artículos.
- Artículos: `BlogPosting` con autora (`Person`: María Paula Fletcher), fechas e imagen.
- `BreadcrumbList` en todas las páginas internas.
- **Nunca** marcar reseñas que no estén visibles en la página.

### 12.4 Técnico

- `sitemap.xml` dinámico (páginas, artículos publicados, productos) y `robots.txt` que bloquea `/panel`, `/reserva/`, `/encuesta/` y `/webhooks/`.
- URLs limpias en minúscula, sin barra final; redirección 301 de `www` a sin `www` y de HTTP a HTTPS.
- Rendimiento objetivo (móvil, 4G): LCP < 2,5 s, CLS < 0,05, INP < 200 ms. Precargar la fuente display y la imagen del hero; CSS crítico en línea opcional.
- Imágenes con dimensiones explícitas (`width`, `height`) para evitar saltos.
- Enlazado interno: pie de página por servicio y zona, artículos que enlazan a la página de servicio correspondiente.
- Google Business Profile: categoría «Maquillador/a», dirección del estudio, área de servicio, botones con UTM, fotos propias y campaña de reseñas (meta: 30–40).

### 12.5 Plan del diario (primeras guías)

| Título | Keyword | Enlaza a |
|---|---|---|
| ¿Cuánto cuesta un maquillaje de novia en Bucaramanga en 2026? | precio maquillaje de novia | Novias |
| Prueba de maquillaje de novia: cuándo hacerla y qué llevar | prueba de maquillaje novia | Novias |
| Maquillaje para boda de día o de noche | maquillaje para boda | Novias |
| Maquillaje para grados: verte bien con toga, flash y calor | maquillaje para grados | Servicios |
| Maquillaje para quinceañera: natural, fotogénico y de su edad | maquillaje para quinceañera | Novias |
| Cómo hacer que el maquillaje dure todo el día en el calor de Bucaramanga | maquillaje de larga duración | Servicios |
| Skin care antes del maquillaje | skin care antes del maquillaje | Servicios |
| Automaquillaje: errores comunes en la piel | curso de automaquillaje | Cursos |
| Cómo empezar a trabajar como maquilladora profesional | curso de maquillaje profesional | Cursos |
| ¿Vale la pena el maquillaje a domicilio? | maquillaje a domicilio bucaramanga | Servicios |

El artículo de precios ya está redactado completo en `Articulo.dc.html`.

---

## 13. Analítica

- **GA4** con Google Tag Manager, cargado solo después del consentimiento (banner con «Aceptar», «Rechazar» y «Configurar»).
- Eventos: `click_whatsapp` (con `lead_code`), `booking_start`, `booking_step` (1–3), `booking_submit` (con servicio y valor), `quote_submit`, `guide_signup`, `begin_checkout`, `purchase` (desde el servidor vía Measurement Protocol al aprobarse el pago).
- **Search Console** verificado por DNS.
- El panel muestra el embudo (visitas, clics, solicitudes, confirmadas, realizadas). Las visitas y clics se leen de GA4 (Data API) o, como alternativa simple, de contadores propios anónimos.

---

## 14. Seguridad (obligatorio)

### 14.1 Transporte y cabeceras

- HTTPS forzado + `Strict-Transport-Security: max-age=31536000; includeSubDomains`.
- `Content-Security-Policy` estricta con nonce por petición: `default-src 'self'; script-src 'self' 'nonce-…' https://checkout.wompi.co https://www.googletagmanager.com; style-src 'self' 'nonce-…'; img-src 'self' data: https:; font-src 'self'; frame-src https://checkout.wompi.co https://www.google.com; connect-src 'self' https://*.google-analytics.com; frame-ancestors 'none'; base-uri 'self'; form-action 'self' https://checkout.wompi.co`. Ajustar a los dominios que exija Wompi según su documentación vigente.
- `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy: camera=(), microphone=(), geolocation=()`, `Cross-Origin-Opener-Policy: same-origin`.
- Sin `X-Powered-By`; `expose_php = Off`.

### 14.2 Sesiones y autenticación

- Cookies de sesión `Secure`, `HttpOnly`, `SameSite=Strict` en el panel (`Lax` en el sitio), nombre propio y `session.use_strict_mode = 1`.
- `session_regenerate_id(true)` al iniciar sesión y al cambiar de rol; caducidad por inactividad de 30 minutos en el panel y absoluta de 12 horas.
- Contraseñas con `password_hash(…, PASSWORD_ARGON2ID)` y `password_needs_rehash`. Mínimo 12 caracteres.
- **2FA TOTP obligatorio** para dueña y socio. Códigos de respaldo de un solo uso, guardados con hash.
- Bloqueo progresivo: 5 intentos fallidos por cuenta y por IP llevan a una espera exponencial. Mensajes de error genéricos.
- Recuperación de contraseña con token aleatorio de un solo uso (hash en BD, caduca en 30 minutos).
- Registro en `audit_log` de cada inicio de sesión, cierre, fallo y cambio de contraseña o 2FA.

### 14.3 Entradas y salidas

- **CSRF**: token sincronizado por sesión en todos los formularios POST (sitio y panel), verificado con `hash_equals`. Los webhooks quedan exentos y se validan por firma.
- **SQL**: solo PDO preparado; nunca concatenar entradas. Nombres de columna para ordenar, solo desde una lista blanca.
- **XSS**: escape en todas las salidas con `e()`; el HTML de los artículos se sanea al guardar con una lista blanca de etiquetas y atributos; JSON-LD con `json_encode(…, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE)`.
- **Validación**: teléfonos normalizados a E.164 (+57), correos con `filter_var`, fechas en el rango permitido, enums contra listas blancas, longitudes máximas.
- **Formularios públicos**: honeypot, tiempo mínimo de llenado, límite de 5 envíos por IP cada 10 minutos y, si hay abuso, Cloudflare Turnstile.
- **Archivos** (comprobantes):
  - solo JPG, PNG, WEBP y PDF, de máximo 5 MB, con verificación del tipo real con `finfo`;
  - las imágenes se recodifican con GD/Imagick para eliminar metadatos y contenido incrustado;
  - se guardan fuera de `/public` con nombre aleatorio;
  - se sirven por un controlador que verifica sesión y rol, con `Content-Disposition: attachment` para PDF.
- **Redirecciones**: solo a rutas internas o a una lista blanca (`wa.me`, Wompi).

### 14.4 Servidor y secretos

- `.env` fuera de `/public` con `APP_KEY` (32 bytes), credenciales de BD, SMTP y llaves de Wompi. Nunca en el repositorio (`.gitignore`).
- `display_errors = Off` en producción; errores a `storage/logs` con rotación; página 500 genérica.
- `.htaccess` en `/public`: solo `index.php` y `/assets` accesibles, sin listado de directorios, bloqueo de `.env`, `.git`, `composer.*` y archivos ocultos.
- Dependencias fijadas y revisadas (`composer audit`).
- Copias de seguridad: base de datos diaria (retener 30 días) y archivos semanal, cifradas y fuera del servidor. Probar la restauración una vez al mes.
- `/.well-known/security.txt` con contacto.

### 14.5 Datos personales (Ley 1581 de 2012)

- Política de tratamiento publicada y enlazada en cada formulario; autorización expresa con casilla (no premarcada en la versión final) y registro de fecha.
- Minimización: solo los datos de la sección 1.3.
- El socio ve teléfonos y correos **enmascarados** (`+57 300 ••• 4412`).
- Derechos de consulta, actualización y supresión: procedimiento por correo. La supresión **anonimiza** la clienta (nombre, correo, teléfono → hash) pero conserva las reservas y comisiones para efectos contables.
- Retención: datos de clientas inactivas por más de 3 años se anonimizan (cron).

---

## 15. Contenido y datos pendientes

- [ ] Horario de atención del estudio.
- [ ] Coordenadas exactas del estudio (para mapa y JSON-LD).
- [ ] Reseñas reales de Google (texto, nombre y tipo de servicio).
- [ ] Fotos originales en alta resolución, sobre todo de novias y quinceañeras.
- [ ] Archivo y licencia web de la fuente Aristotelica Display.
- [ ] Condiciones y costo de desplazamiento fuera del área metropolitana.
- [ ] Fechas y horarios de los próximos grupos de cursos.
- [ ] Precios de los kits y del catálogo de productos de la tienda.
- [ ] Porcentajes de comisión definitivos y ventana de atribución según el acuerdo firmado.
- [ ] Correo de la dueña y del socio para alertas y accesos al panel.
- [ ] Cuenta de Wompi (comercio a nombre de quien recibe el dinero).

---

## 16. Despliegue (Hostinger)

1. PHP 8.3, extensiones: `pdo_mysql`, `mbstring`, `intl`, `sodium`, `fileinfo`, `gd` o `imagick`, `openssl`.
2. Apuntar el dominio a `/public` (o subir el contenido de `public/` a `public_html/` y el resto del proyecto un nivel por encima).
3. Crear la base de datos, ejecutar `database/migrations` en orden y crear los usuarios iniciales con un script de consola (`php cron/crear-usuario.php`), nunca desde la web.
4. Configurar SMTP del dominio con SPF, DKIM y DMARC.
5. Cron jobs:

| Frecuencia | Script | Función |
|---|---|---|
| Cada 15 min | `cron/recordatorios.php` | Recordatorios de 24 h y aviso de 48 h sin abono |
| Cada hora | `cron/encuestas.php` | Encuestas post-servicio |
| Cada hora | `cron/alertas.php` | Alertas al socio; reservas vencidas |
| Lunes 8:00 | `cron/alertas.php --resumen` | Resumen semanal |
| Día 1, 00:10 | `cron/cierre-periodo.php` | Generar el corte del mes anterior |
| Diario 3:00 | `cron/sitemap.php` y respaldo | Sitemap y copia de seguridad |

6. SSL activo y redirecciones 301 (sección 12.4).
7. Verificar Search Console, enviar el sitemap y conectar la ficha de Google.

---

## 17. Orden de construcción y criterios de aceptación

| Fase | Entregable | Criterios de aceptación |
|---|---|---|
| 1 | Base: estructura, router, layout, tokens, componentes C01–C18, cabecera, menú móvil, pie | Coincide con las capturas a 1440, 390 y 360px; sin scroll horizontal; el menú móvil cabe en 667px de alto sin scroll; Lighthouse de accesibilidad ≥ 95 |
| 2 | Páginas públicas estáticas (Inicio, Novias, Servicios, Cursos, Portafolio, Estudio) | Textos y precios idénticos a este README; JSON-LD válido en Rich Results Test; LCP < 2,5 s en móvil |
| 3 | Base de datos y flujo `/reservar` con correos | Reserva creada con código único; correo recibido; evento en el historial; validación y CSRF probados; funciona sin JS |
| 4 | Panel: login con 2FA, resumen, reservas, calendario, clientas | Roles aplicados en el servidor; socio sin permisos de escritura (probado por petición directa); historial no editable y cadena de hashes verificable |
| 5 | Atribución, comisiones, cortes, alertas, encuestas | Casos de prueba de 9.3 a 9.6 cubiertos con tests del dominio; corte cerrado inalterable |
| 6 | Tienda y cursos con Wompi | Pago de prueba aprobado y rechazado; webhook con firma inválida rechazado; pago no confirmado por la URL de retorno |
| 7 | Diario (blog) con administración básica de artículos | Artículo de precios publicado; sitemap actualizado; HTML saneado |
| 8 | Endurecimiento y despliegue | Cabeceras verificadas (securityheaders.com A o superior); copias de seguridad probadas; cron funcionando |

---

## 18. Créditos

Diseño, desarrollo y SEO: **ClikRank** · Carlos Gómez. Marca y contenido: **Make Up by Fletcher** · María Paula Fletcher.
