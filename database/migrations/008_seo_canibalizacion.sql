-- SEO · corrección de canibalización (docs/SEO-AUDITORIA.md §3 y §4).
-- 1. El artículo de precio enlaza a Novias con un ancla descriptiva en vez de «Pack 3», y a la nueva
--    página de quinceañeras.
-- 2. Los borradores del diario que apuntaban a la misma keyword que una página de servicio pasan a la
--    variante informacional y enlazan a la landing correspondiente.
SET NAMES utf8mb4;

UPDATE posts
SET body_html = REPLACE(
        body_html,
        '<p>El <a href="/maquillaje-de-novia-bucaramanga">Pack 3</a> está diseñado para novias y quinceañeras,',
        '<p>El Pack 3 de <a href="/maquillaje-de-novia-bucaramanga">maquillaje de novia en Bucaramanga</a> está diseñado para novias y también sirve para el <a href="/maquillaje-para-quinceaneras-bucaramanga">maquillaje de quinceañera</a>,'
    ),
    updated_at = UTC_TIMESTAMP()
WHERE slug = 'precio-maquillaje-de-novia-bucaramanga'
  AND body_html LIKE '%<a href="/maquillaje-de-novia-bucaramanga">Pack 3</a>%';

-- Grados: el artículo se queda con la intención informacional nacional (ideas y looks);
-- la landing /maquillaje-para-grados-bucaramanga con la transaccional local.
UPDATE posts
SET title = 'Maquillaje para grados: ideas de día, de noche y con toga',
    meta_description = 'Maquillaje para grados: ideas de día, de noche y con toga que aguantan la ceremonia, el flash y la celebración, pensadas para el calor.',
    target_keyword = 'maquillaje para grados',
    links_to = '/maquillaje-para-grados-bucaramanga'
WHERE slug = 'maquillaje-para-grados' AND status = 'borrador';

-- Quinceañera: el artículo apunta a «natural / sencillo»; la landing, a «maquillaje para quinceañeras en Bucaramanga».
UPDATE posts
SET target_keyword = 'maquillaje para quinceañera natural',
    links_to = '/maquillaje-para-quinceaneras-bucaramanga'
WHERE slug = 'maquillaje-para-quinceanera' AND status = 'borrador';

-- Domicilio: el artículo responde la duda («¿vale la pena?»); la landing, la búsqueda local.
UPDATE posts
SET meta_description = '¿Vale la pena el maquillaje a domicilio? Qué se lleva, cuánto espacio necesitas, cuánto cuesta y cuándo conviene más ir al estudio.',
    target_keyword = 'vale la pena el maquillaje a domicilio',
    links_to = '/maquillaje-a-domicilio-bucaramanga'
WHERE slug = 'vale-la-pena-el-maquillaje-a-domicilio' AND status = 'borrador';
