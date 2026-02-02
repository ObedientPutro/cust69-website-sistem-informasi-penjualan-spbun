<?php

namespace App\Services;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Enums\PumpShiftStatusEnum;
use App\Models\Product;
use App\Models\PumpShift;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * 1. METRICS KEUANGAN (Kartu Atas)
     */
    public function getExecutiveStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endLastMonth = Carbon::now()->subMonth()->endOfMonth();

        // Profit Hari Ini (Gross)
        $todayProfit = TransactionItem::whereHas('transaction', fn($q) => $q->whereDate('transaction_date', $today))
            ->get()->sum(fn($i) => ($i->price_per_liter - $i->cost_per_liter) * $i->quantity_liter);

        // Omset Bulan Ini & Growth
        $revenueThisMonth = Transaction::where('transaction_date', '>=', $thisMonth)->sum('grand_total');
        $revenueLastMonth = Transaction::whereBetween('transaction_date', [$lastMonth, $endLastMonth])->sum('grand_total');

        $revenueGrowth = $revenueLastMonth > 0 ? (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100 : 100;

        // Total Piutang Aktif (Unpaid)
        $totalDebt = Transaction::where('payment_status', PaymentStatusEnum::UNPAID)->sum('grand_total');

        // Total Volume Bulan Ini (Liter)
        $volumeThisMonth = TransactionItem::whereHas('transaction', fn($q) => $q->where('transaction_date', '>=', $thisMonth))
            ->sum('quantity_liter');

        return [
            'today_profit' => $todayProfit,
            'revenue_month' => $revenueThisMonth,
            'revenue_growth' => round($revenueGrowth, 1),
            'total_debt' => $totalDebt,
            'volume_month' => $volumeThisMonth
        ];
    }

    /**
     * 2. DATA UNTUK GRAFIK (CHARTS)
     * - Area: Tren Volume (Liter)
     * - Bar: Metode Pembayaran (Cash, Transfer, Bon)
     * - Donut: Status Pembayaran (Lunas vs Belum)
     */
    public function getChartData(): array
    {
        // A. AREA CHART: Tren Volume Per Produk (14 Hari Terakhir)
        $period = CarbonPeriod::create(Carbon::now()->subDays(13), Carbon::now());
        $dates = [];
        foreach ($period as $date) $dates[] = $date->format('d M');

        // Ambil semua produk aktif
        $products = Product::where('is_active', true)->get();
        $volumeSeries = [];

        foreach ($products as $product) {
            $data = [];
            foreach ($period as $date) {
                // Query sum quantity per produk per hari
                $qty = TransactionItem::where('product_id', $product->id)
                    ->whereHas('transaction', fn($q) => $q->whereDate('transaction_date', $date))
                    ->sum('quantity_liter');
                $data[] = (float) $qty;
            }
            $volumeSeries[] = [
                'name' => $product->name,
                'data' => $data
            ];
        }

        // B. LIST: Metode Pembayaran (Sidebar Style)
        $startMonth = Carbon::now()->startOfMonth();
        $methods = Transaction::where('transaction_date', '>=', $startMonth)
            ->select('payment_method', DB::raw('count(*) as count'))
            ->groupBy('payment_method')
            ->get();

        $totalTrx = $methods->sum('count');
        $paymentStats = [];

        // Mapping Enum ke Label & Warna
        $methodConfig = [
            PaymentMethodEnum::CASH->value => ['label' => 'Cash (Tunai)', 'color' => 'bg-green-500', 'text' => 'text-green-600'],
            PaymentMethodEnum::TRANSFER->value => ['label' => 'Transfer', 'color' => 'bg-blue-500', 'text' => 'text-blue-600'],
            PaymentMethodEnum::BON->value => ['label' => 'Bon (Piutang)', 'color' => 'bg-orange-500', 'text' => 'text-orange-600'],
        ];

        foreach ($methodConfig as $key => $config) {
            $count = $methods->where('payment_method', $key)->first()->count ?? 0;
            $percent = $totalTrx > 0 ? ($count / $totalTrx) * 100 : 0;

            $paymentStats[] = [
                'label' => $config['label'],
                'count' => $count,
                'percent' => round($percent, 1),
                'color' => $config['color'],
                'text_color' => $config['text']
            ];
        }

        // C. DONUT CHART: Rasio Lunas vs Belum
        $statuses = Transaction::where('transaction_date', '>=', $startMonth)
            ->select('payment_status', DB::raw('count(*) as count'))
            ->groupBy('payment_status')
            ->pluck('count', 'payment_status')
            ->toArray();

        $paidCount = ($statuses[PaymentStatusEnum::PAID->value] ?? 0) + ($statuses[PaymentStatusEnum::RETURNED->value] ?? 0);
        $unpaidCount = ($statuses[PaymentStatusEnum::UNPAID->value] ?? 0);

        return [
            'volume_series' => [
                'categories' => $dates,
                'series' => $volumeSeries // Array of series
            ],
            'payment_stats' => $paymentStats, // Data List Sidebar
            'debt_ratio_series' => [
                'labels' => ['Lunas', 'Belum Lunas'],
                'data' => [$paidCount, $unpaidCount]
            ]
        ];
    }

    /**
     * 3. INVENTORY HEALTH (Stok Tangki)
     */
    public function getInventoryHealth(): \Illuminate\Support\Collection
    {
        $products = Product::where('is_active', true)->get();
        $last30Days = Carbon::now()->subDays(30);

        return $products->map(function ($p) use ($last30Days) {
            $salesLast30Days = TransactionItem::where('product_id', $p->id)
                ->whereHas('transaction', fn($q) => $q->where('transaction_date', '>=', $last30Days))
                ->sum('quantity_liter');

            $avgDaily = $salesLast30Days / 30;
            $daysToEmpty = $avgDaily > 0 ? round($p->stock / $avgDaily) : 999;

            $status = 'safe';
            if ($p->stock <= 0) $status = 'empty';
            elseif ($daysToEmpty <= 3) $status = 'critical';
            elseif ($daysToEmpty <= 7) $status = 'warning';

            return [
                'id' => $p->id,
                'name' => $p->name,
                'stock' => $p->stock,
                'unit' => $p->unit,
                'days_to_empty' => $daysToEmpty > 365 ? '> 1 Thn' : $daysToEmpty . ' Hari',
                'avg_daily' => round($avgDaily, 1),
                'status' => $status,
            ];
        });
    }

    /**
     * 4. TABEL & LIST (Top Debitur, Transaksi Terakhir, Shift Aktif)
     */
    public function getOperationalLists(): array
    {
        // Top 5 Debitur (Piutang)
        $topDebtors = Transaction::where('payment_status', PaymentStatusEnum::UNPAID)
            ->join('customers', 'transactions.customer_id', '=', 'customers.id')
            // Ganti 'name' dengan 'owner_name'
            ->select('customers.owner_name', 'customers.ship_name', DB::raw('SUM(grand_total) as total_debt'), DB::raw('COUNT(transactions.id) as bon_count'))
            ->groupBy('customers.id', 'customers.owner_name', 'customers.ship_name')
            ->orderByDesc('total_debt')
            ->limit(5)
            ->get();

        // 5 Transaksi Terakhir
        $recentTrx = Transaction::with(['customer', 'items.product'])
            ->latest('transaction_date')
            ->limit(5)
            ->get()
            ->map(fn($t) => [
                'id' => $t->id,
                // Fallback nama customer
                'customer' => $t->customer ? ($t->customer->owner_name ?? $t->customer->manager_name) : 'Umum',
                'items' => $t->items->map(fn($i) => $i->product->name . ' (' . $i->quantity_liter . 'L)')->join(', '),
                'total' => $t->grand_total,
                'status' => $t->payment_status->value,
                'date' => $t->transaction_date->diffForHumans()
            ]);

        // Shift Aktif
        $activeShifts = PumpShift::with(['product', 'opener'])
            ->where('status', PumpShiftStatusEnum::OPEN)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'product_name' => $s->product->name,
                'opener_name' => $s->opener->name,
                'opened_at_time' => $s->opened_at->format('H:i'),
            ]);

        return [
            'debtors' => $topDebtors,
            'recent_transactions' => $recentTrx,
            'active_shifts' => $activeShifts
        ];
    }
}
