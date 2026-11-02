<?php
declare(strict_types=1);

/**
 * Pruebas de la integración con Wompi (§11, fase 6). Contra la BD local:
 *   php tests/WompiTest.php
 * Criterios: pago aprobado y rechazado; webhook con firma inválida rechazado; pago no confirmado por la URL de retorno.
 */

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Domain\Orders\OrderService;
use App\Domain\Orders\WompiSignature;
use App\Http\Request;
use App\Kernel;
use App\Support\Db;

if (config('app.env') === 'production') {
    fwrite(STDERR, "No se ejecuta en producción.\n");
    exit(1);
}
App\Support\Env::set('MAIL_DRIVER', 'log');
App\Support\Env::set('WOMPI_EVENTS_SECRET', 'test_events_local');
App\Support\Env::set('WOMPI_INTEGRITY_SECRET', 'test_integrity_local');

$fails = 0;
$check = static function (string $name, bool $ok) use (&$fails): void {
    echo ($ok ? "OK    " : "FALLO ") . $name . "\n";
    if (!$ok) {
        $fails++;
    }
};

$product = Db::one("SELECT * FROM products WHERE slug = 'curso-automaquillaje'");
$newOrder = static function () use ($product): array {
    return OrderService::create($product, [
        'name' => 'Prueba Wompi', 'email' => 'wompi-test@example.com',
        'phone_e164' => '+573005550' . str_pad((string) random_int(0, 999), 3, '0', STR_PAD_LEFT),
        'gift_recipient' => null, 'gift_message' => null,
    ]);
};
$event = static function (array $order, string $status, ?int $cents = null, string $secret = 'test_events_local', string $txId = ''): array {
    $tx = [
        'id' => $txId !== '' ? $txId : 'tx-' . bin2hex(random_bytes(4)), 'amount_in_cents' => $cents ?? (int) $order['total'] * 100,
        'reference' => $order['reference'], 'currency' => 'COP', 'status' => $status, 'payment_method_type' => 'PSE',
    ];
    $ts = time();
    $props = ['transaction.id', 'transaction.status', 'transaction.amount_in_cents'];
    $checksum = hash('sha256', $tx['id'] . $tx['status'] . $tx['amount_in_cents'] . $ts . $secret);
    return ['event' => 'transaction.updated', 'data' => ['transaction' => $tx], 'environment' => 'test',
        'signature' => ['properties' => $props, 'checksum' => $checksum], 'timestamp' => $ts, 'sent_at' => gmdate('c')];
};
$post = static function (array $body): array {
    $req = new Request('POST', '/webhooks/wompi', [], [], ['REMOTE_ADDR' => '127.0.0.1', 'HTTP_ACCEPT' => 'application/json'], [], [], json_encode($body));
    $res = Kernel::handle($req);
    return [$res->status(), json_decode($res->body(), true)];
};
$status = static fn(array $o): string => (string) Db::value('SELECT status FROM orders WHERE id = ?', [(int) $o['id']]);

// 1. Firma de integridad calculada en el servidor con el monto guardado
$o1 = $newOrder();
$co = OrderService::checkout($o1);
$check('monto en centavos sale del pedido', $co['fields']['amount-in-cents'] === (string) ((int) $product['price'] * 100));
$check('firma de integridad = SHA256(ref+centavos+moneda+secreto)', $co['fields']['signature:integrity'] === hash('sha256', $o1['reference'] . ((int) $product['price'] * 100) . 'COP' . 'test_integrity_local'));
$check('referencia PED-AAMM-NNNN', (bool) preg_match('/^PED-\d{4}-\d{4}$/', (string) $o1['reference']));

// 2. Firma inválida → 401 y el pedido sigue pendiente
[$code, ] = $post($event($o1, 'APPROVED', null, 'secreto-falso'));
$check('webhook con firma inválida responde 401', $code === 401);
$check('firma inválida no aprueba el pedido', $status($o1) === 'pendiente');
$check('evento inválido queda registrado con signature_valid=0', (int) Db::value("SELECT COUNT(*) FROM payment_events WHERE order_reference = ? AND signature_valid = 0", [$o1['reference']]) === 1);

// 3. La URL de retorno no confirma nada
$req = new Request('GET', '/tienda/pedido/' . $o1['reference'], ['t' => $o1['public_token'], 'id' => 'tx-falsa', 'env' => 'test'], [], ['REMOTE_ADDR' => '127.0.0.1'], []);
$res = Kernel::handle($req);
$check('página de retorno responde 200', $res->status() === 200);
$check('página de retorno muestra «verificando» y no aprueba', $status($o1) === 'pendiente' && str_contains($res->body(), 'Estamos verificando tu pago'));
$bad = Kernel::handle(new Request('GET', '/tienda/pedido/' . $o1['reference'], ['t' => 'token-incorrecto'], [], ['REMOTE_ADDR' => '127.0.0.1'], []));
$check('pedido con token incorrecto da 404', $bad->status() === 404);

// 4. Monto que no coincide → no se aprueba
[$code, $body] = $post($event($o1, 'APPROVED', 100));
$check('monto distinto no aprueba', $status($o1) === 'pendiente' && ($body['result'] ?? '') === 'monto_no_coincide');

// 5. Pago rechazado
$o2 = $newOrder();
[$code, $body] = $post($event($o2, 'DECLINED'));
$check('pago rechazado → rechazada', $code === 200 && $status($o2) === 'rechazada');

// 6. Pago aprobado + idempotencia
$txId = 'tx-aprob-' . bin2hex(random_bytes(3));
$ev = $event($o1, 'APPROVED', null, 'test_events_local', $txId);
[$code, $body] = $post($ev);
$check('pago aprobado → aprobada', $code === 200 && $status($o1) === 'aprobada');
$check('guarda id de transacción y fecha de pago', (string) Db::value('SELECT wompi_transaction_id FROM orders WHERE id = ?', [(int) $o1['id']]) === $txId && Db::value('SELECT paid_at FROM orders WHERE id = ?', [(int) $o1['id']]) !== null);
[$code, $body] = $post($ev);
$check('evento repetido es idempotente', $code === 200 && !empty($body['duplicate']));
$check('correo de confirmación registrado una vez', (int) Db::value("SELECT COUNT(*) FROM mail_log WHERE dedupe_key = ? AND status = 'enviado'", ['pedido-' . $o1['reference']]) === 1);

// 7. Un rechazo tardío no deshace un pago aprobado
[$code, $body] = $post($event($o1, 'DECLINED', null, 'test_events_local', $txId));
$check('rechazo posterior no revierte una aprobación', $status($o1) === 'aprobada');

// 8. Tablas de solo inserción
try {
    Db::run('DELETE FROM payment_events WHERE order_reference = ?', [$o1['reference']]);
    $check('payment_events no permite DELETE', false);
} catch (PDOException) {
    $check('payment_events no permite DELETE', true);
}

// 9. Firma: helper con propiedades anidadas y checksum en mayúsculas
$e = $event($o2, 'APPROVED');
$e['signature']['checksum'] = strtoupper($e['signature']['checksum']);
$check('verifyEvent acepta checksum en mayúsculas', WompiSignature::verifyEvent($e, 'test_events_local'));
$e['data']['transaction']['amount_in_cents'] = 1;
$check('verifyEvent rechaza datos alterados', !WompiSignature::verifyEvent($e, 'test_events_local'));

echo $fails === 0 ? "\nTodas las pruebas de Wompi pasaron.\n" : "\n{$fails} prueba(s) fallaron.\n";
exit($fails === 0 ? 0 : 1);
