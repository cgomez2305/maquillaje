<?php
declare(strict_types=1);

namespace App;

use App\Http\Request;

/**
 * Router mínimo: método + patrón con parámetros {nombre} (slug en minúsculas por defecto)
 * o {nombre:regex}. Las rutas se declaran en config/routes.php.
 */
final class Router
{
    /** @var list<array{0:string,1:string,2:array{0:class-string,1:string},3:array<string,mixed>}> */
    private array $routes = [];

    /**
     * @param array{0:class-string,1:string} $handler
     * @param array<string,mixed> $options  p. ej. ['csrf' => false, 'role' => ['owner']]
     */
    public function add(string $methods, string $pattern, array $handler, array $options = []): void
    {
        foreach (explode('|', strtoupper($methods)) as $m) {
            $this->routes[] = [$m, $pattern, $handler, $options];
        }
    }

    /**
     * @return array{handler:array{0:class-string,1:string},params:array<string,string>,options:array<string,mixed>}|null|false
     *         null = no existe la ruta, false = existe con otro método
     */
    public function match(Request $request): array|null|false
    {
        $methodMismatch = false;
        foreach ($this->routes as [$method, $pattern, $handler, $options]) {
            $regex = $this->compile($pattern);
            if (!preg_match($regex, $request->path, $m)) {
                continue;
            }
            if ($method !== $request->method && !($method === 'GET' && $request->method === 'HEAD')) {
                $methodMismatch = true;
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            return ['handler' => $handler, 'params' => $params, 'options' => $options];
        }
        return $methodMismatch ? false : null;
    }

    private function compile(string $pattern): string
    {
        $regex = preg_replace_callback('/\{([a-z_]+)(?::([^}]+))?\}/', static function (array $m): string {
            $inner = $m[2] ?? '[a-z0-9]+(?:-[a-z0-9]+)*';
            return '(?P<' . $m[1] . '>' . $inner . ')';
        }, $pattern);
        return '#^' . $regex . '$#';
    }
}
