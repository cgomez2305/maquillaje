<?php
declare(strict_types=1);

namespace App\Support;

/**
 * GA4 Measurement Protocol desde el servidor (§13): evento purchase al aprobarse un pago.
 * Solo se envía si hay GA4_MEASUREMENT_ID y GA4_API_SECRET, y nunca bloquea el flujo.
 */
final class Analytics
{
    public static function purchase(array $order): void
    {
        $id = (string) env('GA4_MEASUREMENT_ID', '');
        $secret = (string) env('GA4_API_SECRET', '');
        if ($id === '' || $secret === '' || !function_exists('curl_init')) {
            return;
        }
        $payload = [
            'client_id' => 'srv.' . substr(hash('sha256', (string) $order['reference']), 0, 16),
            'events' => [[
                'name' => 'purchase',
                'params' => [
                    'transaction_id' => $order['reference'],
                    'currency' => 'COP',
                    'value' => (int) $order['total'],
                    'items' => [['item_id' => $order['product_slug'], 'item_name' => $order['product_name'], 'price' => (int) $order['total'], 'quantity' => 1]],
                ],
            ]],
        ];
        try {
            $ch = curl_init('https://www.google-analytics.com/mp/collect?measurement_id=' . rawurlencode($id) . '&api_secret=' . rawurlencode($secret));
            curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => json_encode($payload), CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 3, CURLOPT_HTTPHEADER => ['Content-Type: application/json']]);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Throwable) {
        }
    }
}
