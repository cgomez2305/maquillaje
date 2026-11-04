<?php
declare(strict_types=1);

namespace App\Controllers\Site;

use App\Controllers\Controller;
use App\Http\Request;
use App\Http\Response;
use App\Support\Metrics;
use App\Support\WhatsApp;

/**
 * /wa?o=origen&t=tema → cuenta el clic (anónimo, por día) y abre WhatsApp con el mensaje
 * predeterminado del tema. No guarda datos de la persona.
 */
final class WhatsappController extends Controller
{
    private const BOT_UA = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|curl|wget|python|headless|lighthouse/i';

    public function go(Request $request): Response
    {
        $topic = preg_replace('/[^a-z-]/', '', strtolower($request->str('t', 20))) ?: null;
        if ($request->userAgent() !== '' && preg_match(self::BOT_UA, $request->userAgent()) !== 1) {
            Metrics::bump('whatsapp_clicks');
        }
        return Response::redirect(wa_direct(WhatsApp::topicText($topic)), 302)
            ->header('Cache-Control', 'no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
