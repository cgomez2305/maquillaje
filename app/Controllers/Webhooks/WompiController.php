<?php
declare(strict_types=1);

namespace App\Controllers\Webhooks;

use App\Domain\Orders\OrderService;
use App\Domain\Orders\WompiSignature;
use App\Http\RateLimiter;
use App\Http\Request;
use App\Http\Response;
use App\Support\Db;

/**
 * POST /webhooks/wompi (§11): valida la firma del evento con el secreto de eventos, guarda el evento
 * (solo inserción, idempotente) y actualiza el pedido si monto y referencia coinciden.
 * Exento de CSRF; nunca confía en la URL de retorno.
 */
final class WompiController
{
    public function handle(Request $request): Response
    {
        $raw = $request->body();
        if (strlen($raw) > 65536) {
            return Response::json(['error' => 'payload'], 413);
        }
        $event = json_decode($raw, true);
        if (!is_array($event)) {
            return Response::json(['error' => 'json'], 400);
        }
        $valid = WompiSignature::verifyEvent($event, (string) env('WOMPI_EVENTS_SECRET', ''), $request->header('X-Event-Checksum') ?: null);
        $tx = $event['data']['transaction'] ?? [];
        $tx = is_array($tx) ? $tx : [];
        $status = mb_substr(strtoupper((string) ($tx['status'] ?? 'DESCONOCIDO')), 0, 30);
        $ref = preg_match('/^PED-\d{4}-\d{4}$/', (string) ($tx['reference'] ?? '')) ? (string) $tx['reference'] : null;
        // Idempotencia: la misma transacción en el mismo estado se procesa una sola vez
        $eventId = $valid
            ? 'tx:' . mb_substr((string) ($tx['id'] ?? ''), 0, 60) . ':' . $status
            : 'invalid:' . random_token(16);

        if (!$valid && !RateLimiter::hit('wompi_bad:' . $request->ip(), 20, 3600)) {
            // payment_events es de solo inserción: no se deja que eventos falsos la llenen
            return Response::json(['error' => 'signature'], 429);
        }
        // De un evento con firma inválida solo se guarda un resumen acotado (auditoría sin almacenar basura)
        $raw = $valid
            ? json_encode($event, JSON_UNESCAPED_UNICODE)
            : json_encode(['invalid' => true, 'ip' => $request->ip(), 'event' => mb_substr((string) ($event['event'] ?? ''), 0, 60), 'bytes' => strlen($raw)], JSON_UNESCAPED_UNICODE);
        try {
            Db::run(
                'INSERT INTO payment_events (provider, event_id, order_reference, status, raw, signature_valid, received_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                ['wompi', $eventId, $ref, $status, $raw, $valid ? 1 : 0, now_utc()],
            );
        } catch (\PDOException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                return Response::json(['ok' => true, 'duplicate' => true]);
            }
            throw $e;
        }

        if (!$valid) {
            app_log('warning', 'Webhook Wompi con firma inválida', ['ip' => $request->ip(), 'ref' => $ref]);
            return Response::json(['error' => 'signature'], 401);
        }
        if (($event['event'] ?? '') !== 'transaction.updated' || $ref === null) {
            return Response::json(['ok' => true, 'ignored' => true]);
        }
        $result = OrderService::applyTransaction($tx);
        return Response::json(['ok' => true, 'result' => $result]);
    }
}
