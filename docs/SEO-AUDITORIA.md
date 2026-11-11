# Auditoría SEO · Make Up by Fletcher (maquillajebucaramanga.com)

**Fecha:** 11 de noviembre de 2026 · **Alcance:** las 10 URL indexables del sitemap · **Tipo:** análisis y plan. Este documento no cambia el código ni el servidor; todo se implementa después con aprobación del dueño.

---

## 0. Fuentes de datos y método

| Fuente | Qué se usó | Fecha de los datos |
|---|---|---|
| Rastreo propio del sitio en vivo (`curl` + parser HTML) | title, description, canonical, robots, H1–H3, palabras, enlaces internos y anclas, imágenes y `alt`, tipos JSON-LD de las 10 URL del sitemap, más `sitemap.xml` y `robots.txt` | 11-nov-2026 |
| Código del repositorio | `config/seo.php`, `config/app.php`, `app/Support/Schema.php`, `app/Controllers/Site/*`, `app/Views/site/*`, `database/migrations/003_seed_diario.sql`, `docs/ESPECIFICACION.md` §12 | rama `claude/seo-auditoria-mubf` |
| DataForSEO Labs · *Keyword Overview* | volumen mensual, tendencia de 12 meses, competencia de Ads, intención y KD cuando existe | Colombia, español, datos de oct-2026 |
| DataForSEO Labs · *Keyword Suggestions* | long tail que contiene «bucaramanga» y «floridablanca» (filtrado por maquillaje/belleza) | Colombia, español |
| DataForSEO · Google Ads *Search Volume* | contraste de volúmenes | Colombia, español |
| DataForSEO · SERP orgánica en vivo | top 10 móvil para 9 keywords, con local pack, PAA y «También se buscó» | Bucaramanga, Santander, Colombia · 11-nov-2026 |
| DataForSEO Labs · *Ranked Keywords* | keywords por las que rankea el dominio | Colombia: **0 resultados** (dominio nuevo sin visibilidad registrada) |

**Limitaciones (leer antes de usar las cifras):**

- **Los volúmenes son de Colombia, no de Bucaramanga.** Google Ads no da volumen fiable por ciudad para frases tan pequeñas. Las frases que incluyen «bucaramanga», «floridablanca» o «piedecuesta» son locales por definición; las genéricas («maquillaje para grados») son nacionales, y de ellas Bucaramanga capta solo una parte (el área metropolitana es aproximadamente el 2,5 % de la población del país).
- **«s/d»** significa que DataForSEO no devolvió volumen: la frase tiene menos de 10 búsquedas al mes o no está en su base. No es lo mismo que cero. Ejemplos: «maquillaje de novia bucaramanga», «maquilladora floridablanca», «maquillaje para grados bucaramanga» y «maquillaje para quinceañera bucaramanga». La especificación (§12.1) les daba 10–20; hoy no hay datos para confirmarlo.
- **KD** es la dificultad de DataForSEO (0–100) y solo está disponible para algunas keywords. Donde falta, la dificultad se estima a partir de la SERP real (qué tipo de sitios ocupan el top 10).
- No hay Search Console ni Google Business Profile, así que no hay impresiones, posiciones ni datos de Maps.

---

## 1. Resumen ejecutivo

1. **El sitio no rankea por nada todavía** (0 keywords en DataForSEO y ausente del top 10 en las 9 SERP consultadas). Lo técnico está bien; lo que falta es **señal local** (Google Business Profile y reseñas) y **páginas para las intenciones con volumen**.
2. **La SERP de servicio en Bucaramanga es extremadamente débil.** En «maquilladora bucaramanga», «maquillaje de novia bucaramanga», «maquillaje para quinceañera bucaramanga» y «maquillaje a domicilio bucaramanga» el orgánico lo ocupan Instagram, Facebook, AgendaPro y microsites de UENI: **ninguna maquilladora tiene web propia en el top 10**. Una web bien hecha puede entrar al top 3 orgánico en semanas una vez indexada. El **local pack** sí es competido: Christian Maquillaje tiene 4,9 con 110 reseñas.
3. **«maquillaje bucaramanga» (320/mes), la keyword asignada a Inicio, tiene intención de producto:** su local pack y su orgánico son tiendas de cosméticos. Lo mismo pasa con «maquillaje floridablanca» (Estudio) y «maquillaje piedecuesta». Inicio y Estudio deben apuntar a **«maquilladora …» / «maquillaje profesional …»**, que es donde está la intención de servicio.
4. **Dos huecos de volumen sin página propia:**
   - **Grados:** «maquillaje para grados», 1.900/mes con pico de 6.600 en noviembre, más «maquillaje para graduación», 880 con pico de 3.600. Hoy es solo la sección `#grados` de Precios. **Estamos en plena temporada.**
   - **Quinceañeras:** «maquillaje para quinceañeras» 390, «de quinceañera» 320 y «para quinceañera» 210. Hoy el ancla del pie «Maquillaje para quinceañeras» lleva a un H2 llamado «Lo que incluye el Pack 3» (`app/Views/site/novias.php:40`).
5. **Canibalización real en «precio / cuánto cuesta maquillaje de novia»:** responden lo mismo **cuatro URL**: el artículo, Novias, Inicio (FAQ con la pregunta exacta y la ciudad) y Precios. El artículo, que es la página creada para esa intención, recibe el ancla más débil («Pack 3») y la página de Novias no lo enlaza.

---

## 2. Auditoría on-page

### 2.1 Inventario de las URL indexables (rastreo en vivo, 11-nov-2026)

Todas responden 200, tienen canónica a sí mismas, `index, follow`, una sola H1 y títulos de 60 caracteres o menos.

| URL | Title (car.) | Desc. | Palabras* | H2 | JSON-LD propio | Observación principal |
|---|---|---|---|---|---|---|
| `/` | Maquillaje profesional en Bucaramanga · Make Up by Fletcher (59) | 147 | 891 | 7 | FAQPage | Apunta a «maquillaje bucaramanga», que tiene intención de tienda (ver 3.2) |
| `/maquillaje-de-novia-bucaramanga` | Maquillaje de novia en Bucaramanga · Make Up by Fletcher (56) | 147 | 975 | 8 | Service, FAQPage | No enlaza al artículo de precio; el ancla `#quinceaneras` apunta a un H2 genérico |
| `/precios-maquillaje-bucaramanga` | Precios de maquillaje en Bucaramanga 2026 · Fletcher (52) | 149 | 694 | 7 | 3× Service | Grados y domicilio quedan como secciones; sin FAQPage |
| `/cursos-de-maquillaje-bucaramanga` | Cursos de maquillaje en Bucaramanga · Make Up by Fletcher (57) | 144 | 632 | 5 | 2× Course, FAQPage | No responde los PAA de precio/SENA; bio de autora de 1 frase |
| `/portafolio` | Portafolio de maquillaje · Make Up by Fletcher (46) | 144 | **309** | **0** | — | Contenido delgado, sin H2 ni texto por categoría |
| `/estudio-maquillaje-floridablanca` | Estudio de maquillaje en Floridablanca · Fletcher (49) | 149 | **426** | 3 | — | Delgada para ser la página local; sin FAQ ni texto de cómo llegar o de los barrios |
| `/diario` | Diario de belleza · Blog de maquillaje · Fletcher (49) | 146 | 502 | 4 | — | 1 artículo publicado |
| `/diario/precio-maquillaje-de-novia-bucaramanga` | Precio maquillaje de novia en Bucaramanga 2026 · Fletcher (57) | 147 | 897 | 6 | BlogPosting, FAQPage | Canibalizado por Inicio y Novias (ver §3) |
| `/politica-de-privacidad` | (54) | 134 | 966 | 10 | — | OK |
| `/terminos` | (49) | 133 | 776 | 8 | — | OK |

\* Palabras visibles de la página completa, incluidos menú y pie (unas 250 son comunes a todas).

Todas las páginas llevan el `BeautySalon` global y `BreadcrumbList` (excepto Inicio, que no lo necesita).

### 2.2 Hallazgos on-page

Prioridad: **A** = alta, **M** = media, **B** = baja.

| # | P | Hallazgo | Evidencia | Recomendación |
|---|---|---|---|---|
| O1 | A | **Keyword de Inicio con intención equivocada.** «maquillaje bucaramanga» (320/mes) devuelve tiendas de cosméticos tanto en el local pack (Cosméticos Deluxe, So Beauty, Gogó) como en el orgánico (distribuidoras, Instagram de tiendas). «También se buscó»: «tienda de maquillaje bucaramanga centro», «distribuidora de maquillaje…». | `config/seo.php:9-14`; SERP del 11-nov | Principal de Inicio: **«maquilladora en Bucaramanga»** (30 + 30 «maquilladoras en bucaramanga») y **«maquillaje profesional bucaramanga»** (30), que tienen intención de servicio. Dejar «maquillaje bucaramanga» como secundaria. Nuevo title en §6.1. |
| O2 | A | **Sin página para grados** pese a ser el mayor volumen del nicho y estar en temporada (pico en noviembre). | Solo `servicios.php:119` (`#grados`); anclas «Maquillaje para grados» → `/precios-maquillaje-bucaramanga#grados` | Nueva landing local, más un artículo informacional (ver §5 y §6.3). |
| O3 | A | **Sin página para quinceañeras**, y el ancla promete algo que la página no tiene. | El pie enlaza «Maquillaje para quinceañeras» → `novias#quinceaneras`; el `id` está en el H2 «Lo que incluye el Pack 3» (`novias.php:40`); el title de Novias no menciona quinceañeras | Nueva landing `/maquillaje-para-quinceaneras-bucaramanga`. |
| O4 | A | **Estudio es delgada para ser la página local** (426 palabras, 3 H2, sin FAQ). Además la keyword asignada («maquillaje floridablanca») tiene intención de tienda. | Rastreo; SERP «maquillaje floridablanca»: local pack con Pigmentta y Mis Cosméticos | Reorientar a **«maquilladora en Floridablanca»** y ampliar a unas 800 palabras: cómo llegar (Cañaveral, autopista), parqueadero, horario con cita, qué se hace en el estudio frente a domicilio, FAQ con FAQPage y mapa. |
| O5 | M | **Portafolio delgado** (309 palabras, 0 H2). Las categorías son solo filtros. | Rastreo | Un H2 y un párrafo de 40–60 palabras por categoría (Novias, Social, Editorial, Detrás de cámaras), con enlace a la página de servicio correspondiente. Pie de foto con tipo de evento y municipio («Novia en Girón · Pack 3»). |
| O6 | M | **E-E-A-T débil.** No hay página «Sobre mí». El autor del `BlogPosting` enlaza a la página de Estudio. La bio de Cursos es una frase sin años, formación, número de novias ni medios. No hay testimonios en texto. | `Schema.php` `blogPosting()` → `'url' => url('/estudio-maquillaje-floridablanca')`; `cursos.php:75-76` | Crear `/maria-paula-fletcher` (o sección amplia en Estudio) con trayectoria, formación, cifras reales y fotos de trabajo. Marcarla con `Person` (`jobTitle`, `knowsAbout`, `sameAs` a Instagram/TikTok) y usarla como `author.url`. Añadir testimonios reales con nombre y evento (sin marcarlos como `Review` hasta que haya reseñas visibles y verificables). |
| O7 | M | **Datos estructurados incompletos para local**: `geo` es `null`, `openingHoursSpecification` está vacío y `sameAs` no incluye la ficha de Google. Además `Course.provider` es una `Organization` aparte en lugar del `@id` del negocio. | `config/app.php:29,41`; `Schema.php` `course()` | Rellenar coordenadas y horario («con cita»: declarar las franjas reales). Añadir la URL de GBP a `sameAs` cuando exista. Usar `provider: {'@id': businessId}` en `Course`. Añadir `founder.url` hacia la página de María Paula. |
| O8 | M | **Enlazado interno hacia el artículo casi nulo.** Solo lo enlaza `/diario`. Novias, Precios e Inicio no lo enlazan. | Rastreo de anclas | Ver §4. |
| O9 | M | **Precios sin FAQ**, cuando los PAA locales preguntan por precio («¿Cuánto cobra un maquillador a domicilio?», «¿Cuánto cobra una maquilladora por un maquillaje sencillo?»). | SERP «maquillaje a domicilio bucaramanga» | Añadir FAQ con FAQPage en Precios con esas preguntas literales (respuestas con los precios reales). |
| O10 | M | **Cursos no responde la intención de la SERP.** Sus PAA preguntan «¿Cuánto cuesta un curso de maquillaje?», «¿Cómo se llama el curso de maquillaje en el SENA?» y «cursos gratis». Es la SERP más competida (Marlene 292 reseñas, Euroestetika, academias .edu.co). | SERP «cursos de maquillaje bucaramanga» | FAQ con esas preguntas, diferencia frente a técnico laboral/SENA (curso corto, práctico, grupos pequeños), duración en horas, calendario de próximas fechas y requisitos. |
| O11 | B | El título del artículo se repite como H2 en `/diario` (correcto para un listado). Las H1/H2 con `<br>` y sin espacio se extraen pegadas («Aprendea ver la luz», «Tu versióndel sí»). | Rastreo | Opcional: espacio antes del `<br>` para que los extractores no concatenen. |
| O12 | B | Imágenes: `alt` descriptivos en el contenido (bien) y `alt=""` en miniaturas del menú e isotipos (correcto, decorativas). Los nombres de archivo describen la escena pero no el servicio. No hay sitemap de imágenes. | Rastreo | Opcional: sitemap de imágenes del portafolio. No renombrar archivos (poco impacto). |
| O13 | B | Páginas legales indexables con prioridad 0,2. | `sitemap.xml` | Correcto así. No requieren acción. |
| O14 | Info | `/tienda` en `noindex` pese a «tienda de maquillaje bucaramanga» (260). | `config/seo.php` | Correcto: la marca no es una tienda de cosméticos y competir ahí no convierte. Revisar solo si la tienda abre con producto real. |

---

## 3. Auditoría de canibalización

Señales revisadas: title, H1, description, FAQ y JSON-LD, anclas internas y contenido.

### C1 · «precio / cuánto cuesta maquillaje de novia (Bucaramanga)» · **Gravedad alta**

| URL | Señales que compiten |
|---|---|
| `/diario/precio-maquillaje-de-novia-bucaramanga` | title «Precio maquillaje de novia en Bucaramanga 2026»; H1 «¿Cuánto cuesta un maquillaje de novia en Bucaramanga en 2026?»; `target_keyword` en BlogPosting |
| `/` | FAQ (visible y en FAQPage): **«¿Cuánto cuesta un maquillaje de novia en Bucaramanga?»** con la respuesta 600.000 (`HomeController.php:18`) |
| `/maquillaje-de-novia-bucaramanga` | description «… Pack 3 por 600.000 COP»; FAQ «¿Cuánto cuesta el maquillaje de novia?» (`NoviasController.php:19`); precio en el hero (`novias.php:19`) |
| `/precios-maquillaje-bucaramanga` | title «Precios de maquillaje en Bucaramanga 2026»; tarjeta del Pack 3 |

Anclas: el artículo enlaza a Novias con «Pack 3» (seed `003_seed_diario.sql:22`) y «La experiencia novia, paso a paso». Novias no enlaza al artículo.

**Resolución: diferenciar intención y reforzar con anclas.** No hace falta canónica ni fusión.

- **Artículo** es dueño de la intención informacional «cuánto cuesta / precio maquillaje de novia».
- **Novias** es dueña de la transaccional «maquillaje de novia en Bucaramanga». Conserva el precio (es una página de venta), pero su FAQ pasa a «¿Qué incluye el Pack 3?» y su respuesta cierra con «Te explicamos el precio en detalle en ‹precio del maquillaje de novia en Bucaramanga›» (enlace al artículo).
- **Inicio** cambia la pregunta por «¿Cuánto cuestan los servicios?», con un rango de 200.000 a 600.000 y enlace a Precios, y deja de nombrar novia y ciudad en la FAQ.
- En el artículo, el ancla «Pack 3» pasa a ser **«maquillaje de novia en Bucaramanga»**.

### C2 · «maquillaje (profesional) bucaramanga» · Inicio frente a Precios · **Gravedad media**

| URL | Señales |
|---|---|
| `/` | title y H1 «Maquillaje profesional en Bucaramanga»; ancla del pie «Maquillaje en Bucaramanga» → `/` |
| `/precios-maquillaje-bucaramanga` | title «Precios de maquillaje en Bucaramanga 2026»; H1 «Precios de maquillaje en Bucaramanga»; recibe la mayoría de los enlaces de contenido de Inicio («Ver servicios y precios», «Comparar servicios», 8 enlaces con `#packs`/`#domicilio`) |
| `/maquillaje-de-novia-bucaramanga` | H1 «Maquillaje de novia en Bucaramanga…» (con el modificador «novia», menor riesgo) |

Riesgo: Google puede elegir Precios para la consulta genérica porque recibe más enlaces internos con «maquillaje» y «Bucaramanga».

**Resolución:**

- **Inicio** se queda con «maquilladora / maquillaje profesional en Bucaramanga», que es la entidad marca + servicio.
- **Precios** se especializa en «precios / cuánto cuesta» y en **«maquillaje social en Bucaramanga»** (la keyword madre de su Pack 1; «maquillaje social» tiene 1.600/mes nacional).
- Las anclas hacia Precios siempre llevan «precios» o «social» («precios de maquillaje 2026», «maquillaje social para eventos»). El ancla genérica «Maquillaje en Bucaramanga» queda reservada a Inicio.

### C3 · «maquillaje / maquilladora Floridablanca» · Inicio frente a Estudio · **Gravedad media-baja**

| URL | Señales |
|---|---|
| `/estudio-maquillaje-floridablanca` | title y H1 «Estudio de maquillaje en Floridablanca»; ancla del pie «Maquillaje en Floridablanca» |
| `/` | description «… en Bucaramanga y Floridablanca …»; H2 «Floridablanca, a tu medida.» (`home.php:187`); dato «Estudio: Floridablanca»; anclas «Conoce el estudio» y «Cómo llegar» |
| `/maquillaje-de-novia-bucaramanga`, `/cursos-…` | H1 o description con «… y Floridablanca» |

Además, la keyword asignada a Estudio («maquillaje floridablanca», 50) devuelve **tiendas** (Pigmentta, Mis Cosméticos Cañaveral).

**Resolución:**

- **Estudio** es la única dueña de Floridablanca, reorientada a «maquilladora en Floridablanca / estudio de maquillaje» y con contenido ampliado (O4).
- **Inicio** conserva la mención (es la dirección del negocio), pero su H2 pasa a «El estudio, en Floridablanca» con ancla descriptiva **«estudio de maquillaje en Floridablanca»**, en lugar de «Conoce el estudio».
- Novias y Cursos pueden mantener «y Floridablanca» en la description; en el H1 de Novias conviene quitarlo para concentrar la señal («Maquillaje de novia en Bucaramanga»).

### C4 · «maquillaje para quinceañera(s)» · Novias (sin página dedicada) · **Gravedad media (hueco más que canibalización)**

Novias se reparte entre novia y quinceañera (lead «Para novias y quinceañeras…», precio «Novia o quinceañera»), y el ancla `#quinceaneras` aterriza en el Pack 3. Una sola URL no puede rankear bien para dos entidades distintas.

**Resolución:** crear la landing de quinceañeras (§5). Novias se centra en novia y deja un bloque corto «¿Es para unos quince?» que enlaza a la nueva página. El ancla del pie apunta a la nueva URL.

### C5 · «maquillaje para grados» · Precios `#grados` frente a futuras páginas · **Preventivo**

Al crear grados habrá dos piezas. **Landing** `/maquillaje-para-grados-bucaramanga` (transaccional local: «maquillaje para grados en Bucaramanga», Pack 1, reservas de temporada). **Artículo** «Maquillaje para grados: ideas de día, de noche y con toga» (informacional nacional: «maquillaje para grados», «para graduación», «… de día», «… sencillo»), con la intención de inspiración que muestra la SERP (L'Oréal, Pinterest, YouTube, AI Overview).

La sección `#grados` de Precios se reduce a 2–3 líneas y enlaza a la landing. El ancla del pie apunta a la landing.

### C6 · Cursos frente a futuros artículos de automaquillaje y maquillaje profesional · **Preventivo**

**Cursos** se queda con lo local y transaccional («cursos de maquillaje en Bucaramanga», «curso de automaquillaje en Bucaramanga»). Los artículos se quedan con lo informacional nacional («automaquillaje: errores…», «cómo ser maquilladora profesional en Colombia») y enlazan a Cursos con ancla exacta. Ningún artículo usa «curso(s) de maquillaje Bucaramanga» en title ni H1.

---

## 4. Enlazado interno: cambios concretos

| De | A | Ancla actual | Ancla propuesta |
|---|---|---|---|
| Artículo precio novia (párrafo 1) | Novias | «Pack 3» | «maquillaje de novia en Bucaramanga» |
| Novias (FAQ precio y bloque «Del primer mensaje…») | Artículo | — (no existe) | «precio del maquillaje de novia en Bucaramanga» |
| Precios (tarjeta Pack 3) | Artículo | — | «qué incluye el precio de novia» |
| Pie | Landing quinceañeras | «Maquillaje para quinceañeras» → `novias#quinceaneras` | mismo texto → `/maquillaje-para-quinceaneras-bucaramanga` |
| Pie y Precios `#grados` | Landing grados | → `precios#grados` | «Maquillaje para grados» → `/maquillaje-para-grados-bucaramanga` |
| Pie | Landing domicilio | «Maquillaje a domicilio» / «Piedecuesta y Girón a domicilio» → `precios#domicilio` | → `/maquillaje-a-domicilio-bucaramanga` (y `#piedecuesta`, `#giron`) |
| Inicio («Conoce el estudio», «Cómo llegar») | Estudio | genéricas | «estudio de maquillaje en Floridablanca» |
| Inicio (tarjeta Pack 1 «Social y grados») | Precios / grados | «Social y grados» | separar: «maquillaje social» → Precios; «maquillaje para grados» → landing |
| Portafolio (cada categoría) | Servicios | — | Novias → «maquillaje de novia»; Social → «maquillaje social»; categoría nueva Quince → landing |
| Cada artículo nuevo | Su página de servicio + otro artículo | — | ancla exacta de la keyword de la página destino (ver §5) |

Nota: los muchos enlaces a `/reservar` (noindex) desde Novias (6) no hacen daño, pero el primer enlace de contenido de cada bloque debería ser a una página indexable cuando aplique.

---

## 5. Investigación de keywords y mapa

### 5.1 Competencia en el top 10 (SERP móvil Bucaramanga, 11-nov-2026)

| Keyword | Local pack | Top 10 orgánico | Webs propias de maquilladoras | Intención | Debilidad |
|---|---|---|---|---|---|
| maquilladora bucaramanga | Christian Maquillaje (4,9 · 110), Karends Makeup Studio (4,8 · 65), Daniela Florez Academy (5,0 · 1) | 5 redes sociales, Gestabiz, AgendaPro, Euroestetika | **0** | Servicio | **Muy débil** |
| maquillaje de novia bucaramanga | Christian (4,9 · 110), ABSURDA (5,0 · 15), Studios Nick (5,0 · 5) | 5 redes, 3 AgendaPro (ni siquiera de novia), 1 UENI | **0** | Servicio | **Extremadamente débil** |
| maquillaje para quinceañera bucaramanga | Christian, Karends, Lenis Elles Bodas (5,0 · 29) | 5 Instagram/Facebook, 2 AgendaPro | **0** | Servicio | **Extremadamente débil** |
| maquillaje a domicilio bucaramanga | Christian, Foriu (5,0 · 7), Yuly Cabeza (5,0 · 1) | 6 redes, AgendaPro, UENI, 1 tienda | **0** | Servicio (PAA de precio) | **Muy débil** |
| cursos de maquillaje bucaramanga | Instituto Marlene (4,7 · 292), Academia Lady (4,6 · 52), Christian | 4 academias con dominio (.edu.co), Superprof, UENI, redes | 0 (academias) | Formación + precio/SENA | Media |
| maquillaje bucaramanga | Cosméticos Deluxe, So Beauty, Gogó | distribuidoras e Instagram de tiendas | 0 | **Producto (tiendas)** | Irrelevante para servicio |
| maquillaje floridablanca | Mis Cosméticos (4,9 · 203), Pigmentta (4,9 · 916), Fantasía y Belleza | Waze, Facebook, UENI, Marketplace | 0 | **Producto** | Irrelevante para servicio |
| maquillaje piedecuesta | 3 tiendas de cosméticos | Marketplace, Páginas Amarillas, StarOfService («maquilladores en Piedecuesta») | 0 | Producto (hueco de servicio) | Débil |
| maquillaje para grados (nacional) | — | Instagram, L'Oréal, Pinterest, YouTube + **AI Overview** | — | **Informacional / inspiración** | Débil en dominios, pero pide guía |

**PAA y búsquedas relacionadas útiles para contenido:**

- *Domicilio y quinceañera:* «¿Cuánto cobra un maquillador a domicilio?», «¿Cuánto cuesta una maquilladora a domicilio?», «¿Cuánto cobra una maquilladora por un maquillaje sencillo?», «¿Qué es el maquillaje de XV años?».
- *Cursos:* «¿Cuánto cuesta un curso de maquillaje?», «¿Cómo se llama el curso de maquillaje en el SENA?», «Cursos de maquillaje en Bucaramanga gratis».
- *Grados:* «maquillaje para graduación sencillo / natural / elegante / de día con toga / piel morena / universidad».

**Conclusión competitiva.** El orgánico de servicio se gana con contenido. El **local pack** se gana con Google Business Profile y reseñas: el líder (Christian Maquillaje) tiene 110 reseñas y aparece en 6 de los 9 packs. Sin ficha, el sitio no puede entrar al pack, que en móvil aparece por encima del orgánico.

### 5.2 Volúmenes (Colombia, es · DataForSEO, oct-2026)

**Transaccionales locales (servicio)**

| Keyword | Vol./mes | Tendencia / estacionalidad | Competencia Ads | Intención |
|---|---|---|---|---|
| maquillaje bucaramanga | 320 | 210–390 | Media | Producto (SERP) |
| maquilladora bucaramanga | 30 | +33 % interanual; pico 70 en agosto | Baja | Servicio |
| maquilladoras en bucaramanga | 30 | estable | Baja | Servicio |
| maquillaje profesional bucaramanga | 30 | estable | Media | Servicio |
| maquillaje en bucaramanga | 30 | — | Media | Mixta |
| maquillaje de novia bucaramanga | s/d | — | — | Servicio |
| maquillaje para quinceañera bucaramanga | s/d | — | — | Servicio |
| maquillaje a domicilio bucaramanga | 10 | 10–20 | Baja | Transaccional |
| maquillaje floridablanca | 50 | +67 % interanual | Baja | Producto (SERP) |
| maquilladora floridablanca | s/d | — | — | Servicio |
| maquillaje piedecuesta | 70 | +75 % interanual | Baja | Producto/mixta |
| maquillaje giron | 30 | +67 % interanual | Baja | Mixta |
| salón de belleza bucaramanga / floridablanca / piedecuesta | 320 / 90 / 110 | — | Baja (KD 1–51) | Navegacional (peluquerías); no encaja |
| cursos / curso de maquillaje (en) bucaramanga | 40–50 cada variante (grupo de unos 180) | estable (KD 5) | Baja | Comercial |
| academia de belleza (en) bucaramanga | 170 (KD 8) | — | Baja | Comercial (formación técnica; encaje parcial) |
| curso de belleza bucaramanga | 50 (KD 15) | — | Baja | Encaje parcial |
| pestañas pelo a pelo bucaramanga / cejas bucaramanga / lifting de pestañas | 90 / 90 / 50 | — | Baja | **No es un servicio que ofrezcan**: descartar |

**Nacionales con componente local o de servicio**

| Keyword | Vol./mes | Pico | Intención | Uso |
|---|---|---|---|---|
| **maquillaje para grados** | **1.900** | **6.600 (nov-2025)**; 4.400 en diciembre | Informacional | Artículo + landing |
| **maquillaje para graduación** | **880** | **3.600 (nov-2025)** | Informacional | Artículo grados |
| maquillaje social | 1.600 | 2.900 (nov) | Transaccional/mixta | Precios + artículo |
| maquillaje de novia | 880 | 1.000 (oct–nov) | Transaccional | Novias |
| maquillaje para novias | 590 | 880 (oct–dic) | Comercial | Novias |
| maquillaje para boda | 480 | 720 (nov–dic) | Transaccional | Novias / artículo |
| **maquillaje para quinceañeras** | **390** | 480 | Transaccional | Landing quince |
| maquillaje de quinceañera | 320 | 390 | Transaccional | Landing quince |
| maquillaje para quinceañera | 210 | 260 | Transaccional | Landing quince |
| maquillaje novia | 320 | 480 | Transaccional | Novias |
| maquilladora profesional | 320 | 480 | Comercial | Inicio / Cursos |
| curso de maquillaje / cursos de maquillaje | 1.000 | 1.600 (enero) | Informacional | Cursos (secundaria) |
| curso de automaquillaje | 260 | 320 (oct–ene) | Informacional | Cursos + artículo |
| curso de maquillaje profesional | 260 | 320 | Informacional | Cursos + artículo |
| automaquillaje | 140 | 210 | Transaccional | Artículo |
| maquillaje para boda invitada | 140 | 260 (nov–dic) | Transaccional | Artículo |
| maquillaje natural para novia / de novia natural | 140 / 90 | 210 (oct–nov) | Comercial | Artículo |
| maquillaje para novia de día | 90 | 140 | Transaccional | Artículo |
| maquillaje para boda de día | 90 (KD 4) | 210 (nov) | Transaccional | Artículo |
| maquillaje para damas de honor | 90 | 170 (nov–dic) | Transaccional | Artículo |
| maquillaje para piel madura | 90 | 140 | Transaccional | Artículo (mamá de la novia) |
| maquillaje a domicilio | 70 (KD 49) | 140 | Comercial | Landing domicilio |
| maquillaje a prueba de agua | 70 (KD 23) | 90 | Transaccional | Artículo de duración/calor |
| skin care antes del maquillaje | 40 | 50 | Informacional | Artículo |
| maquilladora a domicilio | 30 (KD 49) | 50 | Comercial | Landing domicilio |
| maquillaje y peinado a domicilio | 30 (KD 52) | 50 | Transaccional | **No hacen peinados**: decirlo explícitamente, no atacar |
| maquillaje de novia de noche | 30 (KD 37) | 50 | Transaccional | Artículo |
| maquillaje para sesión de fotos | 40 (KD 51) | 50 | Transaccional | Secundaria de Portafolio/Precios |
| cuánto cuesta un maquillaje de novia / precio maquillaje de novia | 10 / 10 | 20 | Comercial | Artículo existente |
| maquillaje de noche / natural / glam / editorial | 1.600 / 2.900 / 390 / 390 | — | Inspiración nacional | Baja prioridad (dominan medios y marcas; poco cliente local) |

### 5.3 Mapa de keywords: URL existentes (sin solapamientos)

| URL | Keyword principal | Secundarias | Vol. | Intención | Dificultad estimada | Prioridad |
|---|---|---|---|---|---|---|
| `/` | **maquilladora en Bucaramanga** | maquilladoras en bucaramanga · maquillaje profesional bucaramanga · maquillaje bucaramanga · maquilladora profesional | 30 + 30 + 30 (+320 + 320) | Servicio / marca | Baja en orgánico; alta en el pack (reseñas) | **A** |
| `/maquillaje-de-novia-bucaramanga` | **maquillaje de novia en Bucaramanga** | maquillaje para novias · maquillaje de novia · maquillaje para boda · maquilladora de novias | s/d local (880 + 590 + 480 nacional) | Transaccional | Baja (0 webs en el top 10) | **A** |
| `/precios-maquillaje-bucaramanga` | **precios de maquillaje en Bucaramanga** | maquillaje social (Bucaramanga) · cuánto cobra una maquilladora · maquillaje social precio | s/d + 1.600 nac. + 10 + 20 | Comercial | Baja | **A** |
| `/cursos-de-maquillaje-bucaramanga` | **cursos de maquillaje en Bucaramanga** | curso de automaquillaje · curso de maquillaje profesional · academia de maquillaje bucaramanga | ~180 (grupo local) + 260 + 260 nac. + 20 | Comercial | Media (academias, Marlene 292 reseñas) | **M** |
| `/estudio-maquillaje-floridablanca` | **maquilladora en Floridablanca** | estudio de maquillaje Floridablanca · maquillaje floridablanca (secundaria) | s/d + 50 | Servicio local | Baja (sin maquilladoras en el top) | **A** |
| `/portafolio` | **portafolio de maquilladora en Bucaramanga** (marca + imágenes) | maquillaje para sesión de fotos · maquillaje editorial (imágenes) | s/d · 40 · 390 | Navegacional / visual | Baja | **B** |
| `/diario` | **blog de maquillaje** (hub) | marca | — | Hub | — | **B** |
| `/diario/precio-maquillaje-de-novia-bucaramanga` | **cuánto cuesta un maquillaje de novia en Bucaramanga** | precio maquillaje de novia · maquillaje de novia precio | 10 + 10 (+ local s/d) | Comercial-informacional | Muy baja | **M** |

### 5.4 Nuevas URL propuestas

**Páginas de servicio**

| Nueva URL | Keyword principal | Secundarias | Vol. | Intención | Dificultad | Prioridad |
|---|---|---|---|---|---|---|
| `/maquillaje-para-grados-bucaramanga` | **maquillaje para grados en Bucaramanga** | maquillaje de grado · maquillaje para graduación con toga · maquillaje para fotos de grado | s/d local; 1.900 + 880 nacional como paraguas; pico en noviembre | Transaccional (temporada) | Baja local | **A · urgente (temporada)** |
| `/maquillaje-para-quinceaneras-bucaramanga` | **maquillaje para quinceañeras en Bucaramanga** | maquillaje de quinceañera · maquillaje para quince años · maquillaje de XV años natural | 390 + 320 + 210 nac.; local s/d | Transaccional | Muy baja (0 webs en el top 10) | **A** |
| `/maquillaje-a-domicilio-bucaramanga` | **maquillaje a domicilio en Bucaramanga** | maquilladora a domicilio · maquillaje a domicilio en Piedecuesta / Girón / Floridablanca (secciones `#piedecuesta`, `#giron`) · cuánto cobra una maquilladora a domicilio | 10 + 30 + 70 nac.; Piedecuesta 70, Girón 30 | Transaccional | Baja | **M** |
| `/maria-paula-fletcher` (Sobre mí) | **María Paula Fletcher maquilladora** | Make Up by Fletcher | marca | Navegacional / E-E-A-T | — | **M** |
| `/maquilladora-piedecuesta` y `/maquilladora-giron` | maquilladora en Piedecuesta / Girón | — | 70 / 30 (mixta) | Servicio | Baja | **B · condicional:** crearlas solo si Search Console muestra impresiones para esos municipios y hay contenido propio (eventos reales en ese municipio, tiempos de desplazamiento, recargo). Si no, serían páginas puerta. |

**Artículos del diario** (cada uno enlaza a su página de servicio con ancla exacta)

| # | Título propuesto | Keyword objetivo | Secundarias | Vol. (nac.) | Intención | Dificultad | Enlaza a | Prioridad |
|---|---|---|---|---|---|---|---|---|
| D1 | Maquillaje para grados 2026: ideas de día, de noche y con toga | maquillaje para grados | maquillaje para graduación · sencillo · natural · de día | 1.900 + 880 (pico en noviembre) | Informacional | Media (L'Oréal, Pinterest, AI Overview) | Landing grados | **A** |
| D2 | Maquillaje de quinceañera: natural, fotogénico y de su edad | maquillaje para quinceañera natural / sencillo | qué es el maquillaje de XV años | 20 + PAA | Informacional | Baja (KD 24) | Landing quince | **A** |
| D3 | Maquillaje para invitada de boda: qué pedir según la hora y el lugar | maquillaje para boda invitada | maquillaje para boda de día | 140 + 90 | Transaccional-inspiración | Baja (KD 4 en «boda de día») | Precios (Pack 1/2) | **A** (temporada nov–dic) |
| D4 | Maquillaje para damas de honor y mamá de la novia | maquillaje para damas de honor | piel madura · madrina | 90 + 90 + 10 | Transaccional | Baja | Novias (cortejo) | **M** |
| D5 | Maquillaje de novia natural: de día o de noche, cómo elegir | maquillaje natural para novia | novia de día · novia de noche | 140 + 90 + 30 | Comercial | Baja | Novias | **M** |
| D6 | ¿Cuánto cobra una maquilladora a domicilio en Bucaramanga? | cuánto cobra una maquilladora a domicilio | maquilladora a domicilio | PAA ×4 + 30 | Comercial | Baja | Landing domicilio | **M** |
| D7 | Curso de automaquillaje: qué aprendes y cuándo vale la pena | curso de automaquillaje | automaquillaje | 260 + 140 | Informacional | Media | Cursos | **M** (pico oct–ene) |
| D8 | Cómo ser maquilladora profesional en Colombia (y cuánto cuesta formarse) | curso de maquillaje profesional | maquilladora profesional · cuánto cuesta un curso de maquillaje · curso de maquillaje SENA | 260 + 320 + PAA | Informacional | Media | Cursos | **M** |
| D9 | Maquillaje que dura con el calor de Bucaramanga: a prueba de sudor y agua | maquillaje a prueba de agua | cómo hacer que el maquillaje dure todo el día | 70 + 10 | Informacional/transaccional | Baja (KD 23) | Precios | **B** |
| D10 | Maquillaje social: qué es y cuándo elegirlo frente al de novia | maquillaje social (qué es) | maquillaje para fiesta | 1.600 (mixta) + 210 | Informacional | Media | Precios (Pack 1) | **B** |
| D11 | Prueba de maquillaje de novia: cuándo hacerla y qué llevar | prueba de maquillaje novia | — | s/d | Informacional | Muy baja | Novias | **B** (soporta la conversión y el E-E-A-T) |
| D12 | Skin care antes del maquillaje: la rutina que usamos en el estudio | skin care antes del maquillaje | — | 40 | Informacional | Baja | Precios (Pack 2) | **B** |

---

## 6. Plan de acción priorizado

### 6.1 Rápidos de alto impacto (semana 1): solo textos, sin rediseño

| # | Cambio | Archivo | Propuesta |
|---|---|---|---|
| R1 | Title de Inicio | `config/seo.php` `home.title` | **«Maquilladora profesional en Bucaramanga · Fletcher»** (50) |
| R2 | Description de Inicio | `home.description` | «Maquilladora profesional en Bucaramanga para novias, quinceañeras y grados. Estudio en Floridablanca y servicio a domicilio. Reserva por WhatsApp.» (146) |
| R3 | H1 de Inicio (etiqueta) | `app/Views/site/home.php` | «Maquilladora profesional en Bucaramanga» + la frase de marca actual |
| R4 | FAQ de Inicio (C1) | `HomeController.php:18` | Pregunta «¿Cuánto cuestan los servicios?»: «Desde 200.000 COP (maquillaje social) hasta 600.000 COP (novias y quinceañeras, con prueba previa)», más enlace a Precios |
| R5 | FAQ de Novias (C1) | `NoviasController.php:19` | «¿Qué incluye el Pack 3?», con enlace al artículo: «precio del maquillaje de novia en Bucaramanga» |
| R6 | Ancla del artículo (C1) | `003_seed_diario.sql:22` (fila en la BD) | «Pack 3» → «maquillaje de novia en Bucaramanga» |
| R7 | Title y H1 de Estudio (C3, O4) | `config/seo.php` `estudio` | Title **«Maquilladora en Floridablanca · Estudio Fletcher»** (48). H1 «Maquilladora en Floridablanca · estudio con cita». Description: «Estudio de maquillaje en Floridablanca (Carrera 29 #158-08), con cita previa. Luz de estudio, prueba de novia y cursos. Domicilio en el área metropolitana.» (155) |
| R8 | Title de Precios (C2) | `config/seo.php` `servicios` | **«Precios de maquillaje social y de novia 2026 · Fletcher»** (55). H1 «Precios de maquillaje en Bucaramanga · 2026» (igual) |
| R9 | H1 de Novias | `novias.php` | Quitar «y Floridablanca» → «Maquillaje de novia en Bucaramanga» |
| R10 | Datos estructurados | `config/app.php` | `geo` [lat, lng] del estudio; `opening_hours` con las franjas reales de cita; tras crear GBP, su URL en `sameAs` |
| R11 | Search Console | — | Crear la propiedad de dominio (DNS), enviar `sitemap.xml` y pedir indexación de Inicio, Novias, Precios y Estudio |

### 6.2 Semanas 1–3: Google Business Profile (la palanca número 1 para el local pack)

1. **Crear y verificar la ficha** como negocio híbrido: dirección visible (Carrera 29 #158-08, Floridablanca) y área de servicio Bucaramanga, Floridablanca, Piedecuesta y Girón.
2. **Categoría principal:** «Maquillador/a» (*Make-up artist*). **Secundarias:** «Salón de belleza» y la de escuela o academia de maquillaje que ofrezca el selector (verificar los nombres exactos en el momento de crearla). No elegir peluquería: **no hacen peinados**, y conviene decirlo en la descripción para filtrar consultas.
3. **NAP idéntico al sitio:** «Make Up by Fletcher», +57 316 191 0936, la misma dirección. Horario: «Con cita», con franjas declaradas que coincidan con el JSON-LD.
4. **Servicios con precio** (Pack 1, Pack 2, Pack 3, Automaquillaje, Profesional) y **productos** (los mismos packs, con foto).
5. **Botón de sitio web con UTM:** `https://maquillajebucaramanga.com/?utm_source=google&utm_medium=organic&utm_campaign=gbp`. Botón de reserva hacia WhatsApp.
6. **Fotos propias:** de 20 a 30 al inicio (estudio por dentro y por fuera, María Paula trabajando, resultados por servicio) y 2–4 nuevas por semana.
7. **Reseñas.** Meta de 30–40 en 3 meses; el líder del pack tiene 110:
   - Pedir la reseña por WhatsApp el mismo día del servicio, con el enlace corto de reseña (rellenar `google_review_url` en `config/app.php:38`).
   - Pedir que mencionen el servicio y el municipio («maquillaje de novia en Girón»).
   - Responder todas las reseñas.
   - No marcar reseñas en el schema hasta que se muestren en el sitio y sean verificables.
8. **Publicaciones semanales** enlazando a las landings (grados en noviembre y diciembre; novias y quince todo el año).
9. **Preguntas y respuestas:** sembrar las PAA reales («¿Cuánto cobra un maquillador a domicilio?», «¿Hacen peinados?», «¿Con cuánta anticipación reservo?»).
10. **Citas locales consistentes:** perfiles en AgendaPro y UENI (ocupan el top 10 y transfieren algo de autoridad y visibilidad), Bodas.com.co y Matrimonio.com.co, más Páginas Amarillas, siempre con el mismo NAP y enlace a la web.

### 6.3 Semanas 2–6: nuevas páginas

| Orden | Página | Contenido mínimo |
|---|---|---|
| 1 | `/maquillaje-para-grados-bucaramanga` (**publicar ya, en temporada**) | H1 «Maquillaje para grados en Bucaramanga»; Pack 1 con precio; looks de día y de noche; tips de toga y flash; horarios de madrugada; FAQ con FAQPage; Service; galería; CTA a WhatsApp. Title: **«Maquillaje para grados en Bucaramanga · Fletcher»** (48). Description: «Maquillaje para grados en Bucaramanga y Floridablanca: look natural que resiste toga, flash y calor. 200.000 COP con pestañas. Reserva tu fecha.» (144) |
| 2 | `/maquillaje-para-quinceaneras-bucaramanga` | H1 «Maquillaje para quinceañeras en Bucaramanga»; qué incluye (Pack 3, prueba previa); maquillaje acorde a la edad; mamá y cortejo; galería quince; FAQ; Service. Title: **«Maquillaje para quinceañeras en Bucaramanga · Fletcher»** (54). Description: «Maquillaje para quinceañera en Bucaramanga: natural y fotogénico, con prueba previa, video reel y fijación extra. Pack 600.000 COP. Separa la fecha.» (148) |
| 3 | `/maquillaje-a-domicilio-bucaramanga` | H1 «Maquillaje a domicilio en Bucaramanga»; Pack 2; qué lleva el equipo (silla, luz); cobertura con secciones Bucaramanga, Floridablanca, Piedecuesta y Girón (tiempos y condiciones reales); «no hacemos peinados»; FAQ de precio (PAA). Title: **«Maquillaje a domicilio en Bucaramanga · Fletcher»** (48) |
| 4 | `/maria-paula-fletcher` | Trayectoria, formación, cifras reales, filosofía, fotos y prensa; `Person` con `sameAs` |
| 5 | Ampliar Estudio, Portafolio y Cursos | Según O4, O5 y O10 |

Al publicar cada landing: añadirla a `config/routes.php`, `config/seo.php`, el sitemap, el pie, el menú y la tabla de anclas de §4.

### 6.4 Calendario del diario (de noviembre de 2026 a abril de 2027)

| Semana | Artículo | Keyword objetivo | Motivo del momento |
|---|---|---|---|
| 16-nov-2026 | D1 · Maquillaje para grados 2026: ideas de día, de noche y con toga | maquillaje para grados | Pico de noviembre y diciembre (6.600 y 4.400) |
| 23-nov-2026 | D3 · Maquillaje para invitada de boda | maquillaje para boda invitada | Temporada de bodas de diciembre |
| 30-nov-2026 | D4 · Damas de honor y mamá de la novia | maquillaje para damas de honor | Pico nov–dic (170) |
| 14-dic-2026 | D2 · Maquillaje de quinceañera natural | maquillaje para quinceañera natural | Planeación de quinces del primer semestre |
| 11-ene-2027 | D7 · Curso de automaquillaje | curso de automaquillaje | Pico de enero en «curso de maquillaje» (1.600) |
| 25-ene-2027 | D8 · Cómo ser maquilladora profesional en Colombia | curso de maquillaje profesional | Propósitos de año nuevo; PAA del SENA |
| 8-feb-2027 | D5 · Maquillaje de novia natural: de día o de noche | maquillaje natural para novia | Novias que reservan para el segundo semestre |
| 22-feb-2027 | D6 · ¿Cuánto cobra una maquilladora a domicilio? | cuánto cobra una maquilladora a domicilio | PAA ×4 en la SERP local |
| 8-mar-2027 | D11 · Prueba de maquillaje de novia | prueba de maquillaje novia | Soporte de conversión de Novias |
| 22-mar-2027 | D9 · Maquillaje que dura con calor | maquillaje a prueba de agua | Diferencial local (clima) |
| 5-abr-2027 | D12 · Skin care antes del maquillaje | skin care antes del maquillaje | Apoyo al Pack 2 |
| 19-abr-2027 | D10 · Maquillaje social: qué es | maquillaje social | Apoyo a Precios |

Reglas para cada artículo:

- Title de 60 caracteres o menos, con la keyword al inicio.
- H1 con forma de pregunta o promesa.
- FAQ basada en PAA reales.
- Firma de María Paula enlazada a su página.
- Fotos propias con `alt` descriptivo.
- Mínimo 3 enlaces internos: servicio con ancla exacta, otro artículo y Reservar.
- Publicar en `posts` con `target_keyword` y `links_to`.
- **Un artículo por keyword**: no repetir la principal de otra URL del mapa de §5.

### 6.5 Medición (cuando exista Search Console)

- Revisión quincenal de consultas por URL para detectar canibalización real: dos URL con impresiones para la misma consulta.
- Seguimiento de posiciones locales con una malla geográfica (Bucaramanga, Cañaveral, Piedecuesta, Girón) para las keywords de servicio de §5.3.
- En GA4: clics a WhatsApp por landing (`/wa?o=…` ya etiqueta el origen) para medir qué páginas convierten.
- A los 60–90 días, decidir sobre las páginas de municipio (Piedecuesta y Girón) según impresiones reales.

---

## 7. Lo que ya está bien (no tocar)

- Sitemap con `lastmod`; `robots.txt` que solo bloquea `/wa`; canónicas absolutas; `noindex` correcto en `/tienda` y `/reservar`.
- Una H1 por página con la keyword; títulos de 60 caracteres o menos; descriptions de 133–149.
- `lang="es-CO"`; Open Graph y Twitter Card; imágenes con `width` y `height`, carga diferida y AVIF/WebP con preload del LCP; Lighthouse móvil 98–100 (según el contexto del proyecto).
- JSON-LD coherente (BeautySalon con `@id`, Service con Offer en COP, Course, BlogPosting con autora, FAQPage visible) y sin reseñas marcadas.
