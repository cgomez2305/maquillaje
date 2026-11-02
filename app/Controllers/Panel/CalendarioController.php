<?php
declare(strict_types=1);

namespace App\Controllers\Panel;

use App\Controllers\Controller;
use App\Domain\Booking\Availability;
use App\Domain\Booking\BookingAdminService;
use App\Http\Request;
use App\Http\Response;

/** /panel/calendario: vista mensual de citas, bloqueos y festivos. */
final class CalendarioController extends Controller
{
    public function index(Request $request): Response
    {
        $now = new \DateTimeImmutable('now', Availability::tz());
        $mes = $request->str('mes', 7);
        if (preg_match('/^(\d{4})-(\d{2})$/', $mes, $m) && (int) $m[2] >= 1 && (int) $m[2] <= 12 && (int) $m[1] >= 2024 && (int) $m[1] <= 2100) {
            [$y, $mo] = [(int) $m[1], (int) $m[2]];
        } else {
            [$y, $mo] = [(int) $now->format('Y'), (int) $now->format('n')];
        }
        $cal = BookingAdminService::calendar($y, $mo);
        return $this->view('panel/calendario', [
            'title' => 'Calendario',
            'active' => 'calendario',
            'cal' => $cal,
            'today' => $now->format('Y-m-d'),
            'prev' => $cal['start']->modify('-1 month')->format('Y-m'),
            'next' => $cal['start']->modify('+1 month')->format('Y-m'),
        ], 'panel');
    }
}
