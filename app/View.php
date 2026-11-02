<?php
declare(strict_types=1);

namespace App;

/**
 * Plantillas PHP. Las vistas solo contienen bucles, condicionales simples y e().
 * Una vista se renderiza dentro de un layout (layouts/site.php o layouts/panel.php)
 * que recibe $content y los datos de la página ($meta, $schema, $css, $js…).
 */
final class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'site'): string
    {
        $content = self::partial($template, $data);
        if ($layout === null) {
            return $content;
        }
        return self::partial('layouts/' . $layout, $data + ['content' => $content]);
    }

    public static function partial(string $template, array $data = []): string
    {
        if (!preg_match('#^[a-z0-9/_-]+$#', $template)) {
            throw new \InvalidArgumentException('Plantilla no válida');
        }
        $file = BASE_PATH . '/app/Views/' . $template . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('No existe la vista ' . $template);
        }
        $level = ob_get_level();
        ob_start();
        try {
            (static function (string $__file, array $__data): void {
                extract($__data, EXTR_SKIP);
                require $__file;
            })($file, $data);
        } catch (\Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
