<?php
declare(strict_types=1);

namespace App\Domain\Orders;

use App\Domain\Attribution\AttributionService;
use App\Domain\Commission\CommissionService;
use App\Domain\Commission\RateService;
use App\Domain\Notifications\Mailer;
use App\Repositories\ClientRepository;
use App\Support\Analytics;
use App\Support\Counter;
use App\Support\Db;
use App\Support\Metrics;

/**
 * Pedidos en línea de cursos, bonos y productos (§11). El monto siempre sale del servidor;
 * el pedido solo se aprueba con un webhook de Wompi con firma válida y monto/referencia coincidentes.
 */
final class OrderService
{
    /**
     * Valida los datos del comprador. @return array{0:array<string,string>,1:array<string,mixed>}
     */
    public static function validate(array $in, array $product): array
    {
        $e = [];
        $c = [];
        $name = trim(preg_replace('/\s+/u', ' ', (string) ($in['name'] ?? '')) ?? '');
        if (mb_strlen($name) < 3 || mb_strlen($name) > 120 || preg_match('/[<>{}\\\\]/', $name)) {
            $e['name'] = 'Escribe tu nombre completo.';
        }
        $c['name'] = mb_substr($name, 0, 120);
        $email = mb_strtolower(trim((string) ($in['email'] ?? '')));
        if (mb_strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'Escribe un correo válido: ahí te llega la confirmación.';
        }
        $c['email'] = $email;
        $phone = phone_e164((string) ($in['phone'] ?? ''));
        if ($phone === null) {
            $e['phone'] = 'Escribe un número de WhatsApp válido, por ejemplo 300 123 4567.';
        }
        $c['phone_e164'] = $phone;
        $c['gift_recipient'] = null;
        $c['gift_message'] = null;
        if ($product['category'] === 'gift') {
            $rec = trim((string) ($in['gift_recipient'] ?? ''));
            if (mb_strlen($rec) < 2 || mb_strlen($rec) > 120) {
                $e['gift_recipient'] = 'Escribe el nombre de quien recibe el bono.';
            }
            $c['gift_recipient'] = mb_substr($rec, 0, 120);
            $msg = trim((string) ($in['gift_message'] ?? ''));
            if (mb_strlen($msg) > 300) {
                $e['gift_message'] = 'El mensaje puede tener hasta 300 caracteres.';
            }
            $c['gift_message'] = $msg !== '' ? $msg : null;
        }
        if (empty($in['consent'])) {
            $e['consent'] = 'Necesitamos tu autorización para procesar la compra.';
        }
        if (empty($in['terms'])) {
            $e['terms'] = 'Acepta los términos de compra para continuar.';
        }
        return [$e, $c];
    }

    /** Crea el pedido pendiente. @return array<string,mixed> */
    public static function create(array $product, array $buyer): array
    {
        $utm = AttributionService::current();
        $order = Db::transaction(static function () use ($product, $buyer, $utm): array {
            $p = Db::one('SELECT * FROM products WHERE id = ? AND active = 1 FOR UPDATE', [(int) $product['id']]);
            if ($p === null || (int) $p['price'] <= 0) {
                throw new \RuntimeException('Producto no disponible');
            }
            if ($p['stock'] !== null && (int) $p['stock'] < 1) {
                throw new \RuntimeException('Producto agotado');
            }
            $client = ClientRepository::upsert([
                'phone_e164' => $buyer['phone_e164'], 'name' => $buyer['name'], 'email' => $buyer['email'],
            ], 'web_form', true, $utm);
            $reference = Counter::code('PED');
            $now = now_utc();
            $total = (int) $p['price'];
            $id = Db::insert(
                'INSERT INTO orders (reference, client_id, total, status, commission_rate_bp, is_web_attributed, shipping, public_token, created_at, updated_at)
                 VALUES (?, ?, ?, \'pendiente\', ?, 1, ?, ?, ?, ?)',
                [
                    $reference, $client['id'], $total, RateService::current((string) $p['category']),
                    json_encode(array_filter(['gift_message' => $buyer['gift_message'], 'recipient' => $buyer['gift_recipient']]), JSON_UNESCAPED_UNICODE),
                    random_token(32), $now, $now,
                ],
            );
            Db::run('INSERT INTO order_items (order_id, product_id, qty, unit_price, gift_recipient) VALUES (?, ?, 1, ?, ?)', [$id, (int) $p['id'], $total, $buyer['gift_recipient']]);
            return (array) self::find($id);
        });
        Metrics::bump('checkouts');
        return $order;
    }

    /** @return array<string,mixed>|null */
    public static function find(int $id): ?array
    {
        return Db::one(self::SELECT . ' WHERE o.id = ?', [$id]);
    }

    /** @return array<string,mixed>|null */
    public static function findByReference(string $ref): ?array
    {
        return Db::one(self::SELECT . ' WHERE o.reference = ?', [$ref]);
    }

    public const SELECT = 'SELECT o.*, c.name AS client_name, c.email AS client_email, c.phone_e164,
                                  p.name AS product_name, p.slug AS product_slug, p.category AS product_category, p.service_code, oi.id AS item_id, oi.gift_recipient
                           FROM orders o JOIN clients c ON c.id = o.client_id
                           JOIN order_items oi ON oi.order_id = o.id JOIN products p ON p.id = oi.product_id';

    /**
     * Parámetros del Web Checkout de Wompi. El monto en centavos sale del pedido guardado.
     * @return array{action:string,fields:array<string,string>}
     */
    public static function checkout(array $order): array
    {
        $cents = (int) $order['total'] * 100;
        $fields = [
            'public-key' => (string) env('WOMPI_PUBLIC_KEY', ''),
            'currency' => 'COP',
            'amount-in-cents' => (string) $cents,
            'reference' => (string) $order['reference'],
            'signature:integrity' => WompiSignature::integrity((string) $order['reference'], $cents, 'COP', (string) env('WOMPI_INTEGRITY_SECRET', '')),
            'redirect-url' => url('/tienda/pedido/' . $order['reference'] . '?t=' . $order['public_token']),
            'customer-data:email' => (string) $order['client_email'],
            'customer-data:full-name' => (string) $order['client_name'],
        ];
        if (preg_match('/^\+57(\d{10})$/', (string) $order['phone_e164'], $m)) {
            $fields['customer-data:phone-number'] = $m[1];
            $fields['customer-data:phone-number-prefix'] = '+57';
        }
        return ['action' => 'https://checkout.wompi.co/p/', 'fields' => $fields];
    }

    /**
     * Procesa un evento transaction.updated ya verificado. Devuelve un código de resultado para el registro.
     * @param array<string,mixed> $tx data.transaction
     */
    public static function applyTransaction(array $tx): string
    {
        $ref = (string) ($tx['reference'] ?? '');
        $status = strtoupper((string) ($tx['status'] ?? ''));
        $result = Db::transaction(static function () use ($tx, $ref, $status): string {
            $o = Db::one('SELECT * FROM orders WHERE reference = ? FOR UPDATE', [$ref]);
            if ($o === null) {
                return 'pedido_inexistente';
            }
            if ((int) ($tx['amount_in_cents'] ?? -1) !== (int) $o['total'] * 100 || strtoupper((string) ($tx['currency'] ?? '')) !== 'COP') {
                app_log('warning', 'Wompi: monto o moneda no coinciden', ['ref' => $ref]);
                return 'monto_no_coincide';
            }
            $map = ['APPROVED' => 'aprobada', 'DECLINED' => 'rechazada', 'ERROR' => 'rechazada', 'VOIDED' => 'anulada'];
            if (!isset($map[$status])) {
                return 'estado_ignorado';
            }
            $new = $map[$status];
            if ($o['status'] === $new) {
                return 'sin_cambio';
            }
            if (in_array($o['status'], ['aprobada', 'reembolsada'], true) && $new !== 'anulada') {
                return 'ya_aprobada';
            }
            $now = now_utc();
            Db::run(
                'UPDATE orders SET status = ?, wompi_transaction_id = ?, paid_at = ?, updated_at = ? WHERE id = ?',
                [$new, mb_substr((string) ($tx['id'] ?? ''), 0, 64), $new === 'aprobada' ? $now : $o['paid_at'], $now, (int) $o['id']],
            );
            if ($new === 'aprobada') {
                $item = Db::one('SELECT oi.id, oi.gift_recipient, p.id AS pid, p.category, p.service_code, p.stock FROM order_items oi JOIN products p ON p.id = oi.product_id WHERE oi.order_id = ?', [(int) $o['id']]);
                if ($item !== null && $item['stock'] !== null) {
                    Db::run('UPDATE products SET stock = GREATEST(stock - 1, 0) WHERE id = ?', [(int) $item['pid']]);
                }
                if ($item !== null && $item['category'] === 'gift' && !Db::value('SELECT 1 FROM gift_codes WHERE order_item_id = ?', [(int) $item['id']])) {
                    \App\Domain\Orders\GiftCodeService::generate((int) $item['id'], (string) $item['service_code'], (int) $o['total'], $item['gift_recipient']);
                }
            }
            if ($new === 'anulada' && $o['status'] === 'aprobada') {
                Db::run("UPDATE gift_codes SET status = 'anulado' WHERE order_item_id IN (SELECT id FROM order_items WHERE order_id = ?) AND status = 'activo'", [(int) $o['id']]);
            }
            return $new;
        });

        if ($result === 'aprobada') {
            $order = (array) self::findByReference($ref);
            CommissionService::recordOrder((int) $order['id']);
            self::sendConfirmation($order);
            Analytics::purchase($order);
        }
        return $result;
    }

    /** Reembolso hecho por fuera (en el comercio de Wompi): la dueña lo registra en el panel. */
    public static function markRefunded(int $orderId, int $userId, string $reason): ?string
    {
        $o = Db::one('SELECT status FROM orders WHERE id = ?', [$orderId]);
        if ($o === null || $o['status'] !== 'aprobada') {
            return 'Solo un pedido aprobado se puede marcar como reembolsado.';
        }
        if (mb_strlen(trim($reason)) < 4) {
            return 'Escribe el motivo del reembolso.';
        }
        Db::run("UPDATE orders SET status = 'reembolsada', refunded_at = ?, updated_at = ? WHERE id = ?", [now_utc(), now_utc(), $orderId]);
        Db::run("UPDATE gift_codes SET status = 'anulado' WHERE order_item_id IN (SELECT id FROM order_items WHERE order_id = ?) AND status = 'activo'", [$orderId]);
        CommissionService::recordRefund($orderId);
        \App\Domain\Audit\AuditLog::record($userId, 'pedido_reembolsado', 'order:' . $orderId, ['motivo' => $reason]);
        return null;
    }

    public static function sendConfirmation(array $order): void
    {
        $codes = Db::all('SELECT code, recipient, value FROM gift_codes WHERE order_item_id = ?', [(int) $order['item_id']]);
        $statusUrl = url('/tienda/pedido/' . $order['reference'] . '?t=' . $order['public_token']);
        $ok = Mailer::send((string) $order['client_email'], 'Pago aprobado · ' . $order['product_name'], 'pedido-aprobado', [
            'o' => $order, 'codes' => $codes, 'statusUrl' => $statusUrl,
        ], 'pedido-' . $order['reference']);
        if ($ok) {
            Db::run('UPDATE orders SET confirmation_sent_at = COALESCE(confirmation_sent_at, ?) WHERE id = ?', [now_utc(), (int) $order['id']]);
        }
        $owner = Mailer::owner();
        if ($owner !== '') {
            Mailer::send($owner, 'Venta en línea ' . $order['reference'] . ' · ' . $order['product_name'], 'pedido-duena', ['o' => $order, 'codes' => $codes], 'pedido-duena-' . $order['reference']);
        }
    }
}
