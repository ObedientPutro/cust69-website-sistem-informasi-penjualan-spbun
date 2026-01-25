<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
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
        return Inertia::render('Dashboard', [
            'financial' => $this->dashboardService->getFinancialStats(),
            'inventory' => $this->dashboardService->getStockAnalysis(),
            'trends'    => $this->dashboardService->getProductSalesTrend(),
            'lists'     => $this->dashboardService->getTopLists(),
        ]);
    }
}
