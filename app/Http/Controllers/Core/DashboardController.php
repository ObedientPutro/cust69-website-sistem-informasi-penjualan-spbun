<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\PumpShift;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function __invoke()
    {
        $metrics = $this->dashboardService->getExecutiveStats();
        $charts = $this->dashboardService->getChartData();
        $lists = $this->dashboardService->getOperationalLists();
        $inventory = $this->dashboardService->getInventoryHealth();

        return Inertia::render('Dashboard/Dashboard', [
            'financial' => $metrics,
            'inventory' => $inventory,
            'trends' => [
                'volume_series' => $charts['volume_series'],
                'payment_stats' => $charts['payment_stats'],
                'debt_ratio_series' => $charts['debt_ratio_series'],
            ],
            'lists' => [
                'debtors' => $lists['debtors'],
                'recent_transactions' => $lists['recent_transactions'],
            ],
            'active_shifts' => $lists['active_shifts'],
        ]);
    }
}
