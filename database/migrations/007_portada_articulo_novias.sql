-- Portada del artículo de precios de novia (también es su imagen para compartir y para Google Discover):
-- pasa del detalle de ojo del catálogo a una foto profesional de novia en alta.
UPDATE posts SET cover = 'fotos/novia-tiara-ramo'
WHERE slug = 'precio-maquillaje-de-novia-bucaramanga' AND cover = 'fotos/catalogo-ojo-detalle';
