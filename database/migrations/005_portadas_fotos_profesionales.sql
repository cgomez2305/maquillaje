-- Portadas del diario: las fotos de Instagram (640 px, ampliadas) pasan a las fotos profesionales en alta.
-- Solo cambia las portadas que siguen siendo las del contenido inicial (si la dueña ya eligió otra, no se toca).
UPDATE posts SET cover = 'fotos/novia-bata-sonrisa' WHERE cover = 'fotos/ig-novia-reel';
UPDATE posts SET cover = 'fotos/novia-bata-ramo' WHERE cover = 'fotos/ig-clienta-bata-seda';
UPDATE posts SET cover = 'fotos/social-cumple-pastel' WHERE cover = 'fotos/ig-maquillaje-social';
