<?php
declare(strict_types=1);

namespace App\Controllers\Panel;

use App\Controllers\Controller;
use App\Domain\Alerts\AlertService;
use App\Domain\Commission\ReportService;
use App\Http\Request;
use App\Http\Response;

/** /panel · Resumen del mes (dueña y socio). */
final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        [$y, $m] = ReportService::parseMonth($request->str('mes', 7));
        $data = ReportService::dashboard($y, $m);
        $start = $data['range']['start'];
        $now = local_time(now_utc());
        return $this->view('panel/resumen', [
            'title' => 'Resumen',
            'active' => 'resumen',
            'd' => $data,
            'year' => $y,
            'month' => $m,
            'prev' => $start->modify('-1 month')->format('Y-m'),
            'next' => $start->modify('+1 month') <= $now ? $start->modify('+1 month')->format('Y-m') : null,
            'alerts' => AlertService::recent(6, 45),
        ], 'panel');
    }
}
