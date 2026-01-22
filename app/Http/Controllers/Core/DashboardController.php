<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'total_sales' => 15000000,
                'total_orders' => 120,
                'total_products' => 5,
                'total_users' => 5,
            ]
        ]);
    }
}
