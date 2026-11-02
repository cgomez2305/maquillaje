<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Domain\Catalog;
use App\Domain\Orders\OrderService;
use App\Http\FormGuard;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\ProductRepository;
use App\Support\Schema;

/**
 * Tienda (maqueta Tienda.dc.html) y ficha de producto con formulario de compra (Wompi, §11).
 */
final class TiendaController extends Controller
{
    public function index(Request $request): Response
    {
        $gifts = $this->safe(static fn(): array => ProductRepository::bySubcategory('bonos'));
        $kits = $this->safe(static fn(): array => ProductRepository::bySubcategory('kits', false));

        $schema = [];
        if ($gifts !== []) {
            $schema[] = [
                '@context' => 'https://schema.org',
                '@type' => 'ItemList',
                'name' => 'Bonos de regalo de Make Up by Fletcher',
                'itemListElement' => array_map(fn(array $p, int $i): array => [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'url' => url('/tienda/' . $p['slug']),
                    'name' => $p['name'],
                ], $gifts, array_keys($gifts)),
            ];
        }

        return $this->page('tienda', 'site/tienda', [
            'gifts' => $gifts,
            'kits' => $kits,
            'css' => ['pages/tienda.css'],
            'schema' => $schema,
            'waOrigin' => 'tienda',
        ]);
    }

    public function show(Request $request): Response
    {
        $product = $this->safe(static fn(): ?array => ProductRepository::findActiveBySlug($request->param('slug')), null);
        if ($product === null) {
            throw new HttpException(404);
        }
        return $this->renderProduct($product);
    }

    /** POST /tienda/{slug}/comprar → valida, crea el pedido pendiente y lleva al checkout de Wompi. */
    public function comprar(Request $request): Response
    {
        $product = $this->safe(static fn(): ?array => ProductRepository::findActiveBySlug($request->param('slug')), null);
        if ($product === null || !ProductRepository::isPurchasable($product)) {
            throw new HttpException(404);
        }
        $old = [];
        foreach (['name' => 120, 'phone' => 30, 'email' => 190, 'gift_recipient' => 120, 'gift_message' => 300] as $f => $len) {
            $old[$f] = $request->str($f, $len);
        }
        $old['consent'] = $request->input('consent') === '1' ? '1' : '';
        $old['terms'] = $request->input('terms') === '1' ? '1' : '';

        [$errors, $buyer] = OrderService::validate($old, $product);
        if ($errors === []) {
            $spam = FormGuard::check($request, 'compra');
            if ($spam !== null) {
                return $this->renderProduct($product, [], $old, $spam, 422);
            }
            try {
                $order = OrderService::create($product, $buyer);
            } catch (\RuntimeException $e) {
                return $this->renderProduct($product, [], $old, 'Este producto ya no está disponible. Escríbenos por WhatsApp.', 409);
            }
            return Response::redirect('/tienda/pedido/' . $order['reference'] . '?t=' . $order['public_token'] . '&pagar=1', 303);
        }
        return $this->renderProduct($product, $errors, $old, null, 422);
    }

    /**
     * GET /tienda/pedido/{reference}?t=… — antes del pago muestra el botón a Wompi; al volver de Wompi
     * solo muestra el estado guardado («estamos verificando tu pago»): nunca confirma por la URL.
     */
    public function pedido(Request $request): Response
    {
        $order = OrderService::findByReference($request->param('reference'));
        $token = $request->str('t', 60);
        if ($order === null || $token === '' || !hash_equals((string) $order['public_token'], $token)) {
            throw new HttpException(404);
        }
        $codes = $order['status'] === 'aprobada' ? \App\Support\Db::all('SELECT code, recipient, value FROM gift_codes WHERE order_item_id = ?', [(int) $order['item_id']]) : [];
        $returned = $request->str('id', 80) !== '' || $request->str('env', 20) !== '';
        return $this->page('tienda', 'site/pedido', [
            'o' => $order,
            'codes' => $codes,
            'pay' => $order['status'] === 'pendiente' && !$returned,
            'autopay' => $request->str('pagar', 2) === '1' && $order['status'] === 'pendiente',
            'returned' => $returned,
            'checkout' => OrderService::checkout($order),
            'path' => '/tienda',
            'meta' => ['title' => 'Pedido ' . $order['reference'] . ' · Make Up by Fletcher', 'description' => 'Estado de tu pedido', 'robots' => 'noindex, nofollow', 'canonical' => '/tienda'],
            'crumbs' => [['Inicio', '/'], ['Tienda', '/tienda'], ['Pedido ' . $order['reference'], null]],
            'css' => ['pages/tienda.css', 'pages/reservar.css'],
            'js' => ['pedido.js'],
            'hideFab' => true,
        ]);
    }

    /**
     * Ficha de producto. Reutilizable para volver a mostrar el formulario con errores.
     *
     * @param array<string,string> $errors  campo => mensaje
     * @param array<string,string> $old     valores enviados
     */
    protected function renderProduct(array $product, array $errors = [], array $old = [], ?string $notice = null, int $status = 200): Response
    {
        $path = '/tienda/' . $product['slug'];
        $isGift = $product['category'] === 'gift';
        $isCourse = $product['category'] === 'course';

        $title = $product['name'] . ' · Tienda Fletcher';
        if (mb_strlen($title) > 60) {
            $title = mb_substr($product['name'], 0, 57) . '…';
        }
        $description = trim(($product['summary'] ?? '') . ' ' . ($isGift ? 'Bono de regalo con código único.' : '') . ' Pago seguro en línea con Wompi.');
        if (mb_strlen($description) > 155) {
            $description = mb_substr($description, 0, 154) . '…';
        }

        $image = $product['image'] ? $this->largestImage((string) $product['image']) : null;

        return $this->page('tienda', 'site/producto', [
            'product' => $product,
            'isGift' => $isGift,
            'isCourse' => $isCourse,
            'includes' => $this->includes($product),
            'related' => $this->safe(static fn(): array => ProductRepository::related($product), []),
            'purchasable' => ProductRepository::isPurchasable($product),
            'errors' => $errors,
            'old' => $old,
            'notice' => $notice,
            'path' => $path,
            'meta' => [
                'title' => $title,
                'description' => $description,
                'canonical' => $path,
                'og_type' => 'product',
                'og_image' => $image !== null ? url($image) : null,
            ],
            'crumbs' => [['Inicio', '/'], ['Tienda', '/tienda'], [$product['name'], null]],
            'css' => ['pages/tienda.css'],
            'schema' => [Schema::product($product['name'], (string) ($product['description'] ?? $product['summary'] ?? ''), $product['price'], $path, $image)],
            'preload' => $product['image'] ? ['key' => (string) $product['image'], 'sizes' => '(max-width: 960px) 100vw, 560px'] : null,
            'waOrigin' => 'tienda-' . $product['slug'],
        ], $status);
    }

    /** Lo que incluye: incluidos del pack del bono o módulos del curso (config/catalog.php). */
    private function includes(array $product): array
    {
        $code = (string) ($product['service_code'] ?? '');
        $all = Catalog::all();
        if ($code === '' || !isset($all[$code])) {
            return [];
        }
        $item = $all[$code];
        if (isset($item['includes'])) {
            return $item['includes'];
        }
        if (isset($item['modules'])) {
            $list = ['Formato: ' . $item['format'], 'Contenido: ' . implode(', ', $item['modules'])];
            if (!empty($item['requires_model'])) {
                $list[] = 'Requiere modelo de práctica';
            }
            $list[] = 'Clases en el estudio de Floridablanca';
            $list[] = 'Lista de materiales con descuento en la tienda aliada';
            return $list;
        }
        return [];
    }

    /** Ruta JPG más grande disponible para una foto del manifiesto (Open Graph y JSON-LD). */
    private function largestImage(string $key): ?string
    {
        static $manifest = null;
        if ($manifest === null) {
            $file = BASE_PATH . '/public/assets/img/manifest.json';
            $manifest = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
        }
        $m = $manifest[$key] ?? null;
        return $m ? '/assets/img/' . $key . '-' . end($m['widths']) . '.' . $m['ext'] : null;
    }

    /**
     * La tienda nunca se cae por la BD: sin conexión muestra la página sin productos dinámicos.
     *
     * @template T
     * @param callable():T $fn
     * @param T $fallback
     * @return T
     */
    private function safe(callable $fn, mixed $fallback = []): mixed
    {
        try {
            return $fn();
        } catch (\PDOException $e) {
            app_log('error', 'Tienda sin BD: ' . $e->getMessage());
            return $fallback;
        }
    }
}
