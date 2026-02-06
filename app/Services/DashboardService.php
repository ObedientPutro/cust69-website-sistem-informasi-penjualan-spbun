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
     * METRICS KEUANGAN (Kartu Atas)
     */
    public function getExecutiveStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endLastMonth = Carbon::now()->subMonth()->endOfMonth();

        // Profit Hari Ini (Gross)
        $todayProfit = TransactionItem::whereHas('transaction', fn($q) => $q->valid()->whereDate('transaction_date', $today))
            ->get()->sum(fn($i) => ($i->price_per_liter - $i->cost_per_liter) * $i->quantity_liter);

        // Omset Bulan Ini & Growth
        $revenueThisMonth = Transaction::valid()->where('transaction_date', '>=', $thisMonth)->sum('grand_total');
        $revenueLastMonth = Transaction::valid()->whereBetween('transaction_date', [$lastMonth, $endLastMonth])->sum('grand_total');

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
     * DATA UNTUK GRAFIK (CHARTS)
     */
    public function getChartData(): array
    {
        // A. AREA CHART: Tren Volume Per Produk (14 Hari Terakhir)
        $period = CarbonPeriod::create(Carbon::now()->subDays(13), Carbon::now());
        $dates = [];
        foreach ($period as $date) $dates[] = $date->format('d M');

        $products = Product::where('is_active', true)->get();
        $volumeSeries = [];

        foreach ($products as $product) {
            $data = [];
            foreach ($period as $date) {
                $qty = TransactionItem::where('product_id', $product->id)
                    ->whereHas('transaction', fn($q) => $q->valid()->whereDate('transaction_date', $date))
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
        $methods = Transaction::valid()
            ->where('transaction_date', '>=', $startMonth)
            ->select('payment_method', DB::raw('count(*) as count'))
            ->groupBy('payment_method')
            ->get();

        $totalTrx = $methods->sum('count');
        $paymentStats = [];

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
        $statuses = Transaction::valid()
            ->where('transaction_date', '>=', $startMonth)
            ->select('payment_status', DB::raw('count(*) as count'))
            ->groupBy('payment_status')
            ->pluck('count', 'payment_status')
            ->toArray();

        $paidCount = ($statuses[PaymentStatusEnum::PAID->value] ?? 0);
        $unpaidCount = ($statuses[PaymentStatusEnum::UNPAID->value] ?? 0);

        return [
            'volume_series' => [
                'categories' => $dates,
                'series' => $volumeSeries
            ],
            'payment_stats' => $paymentStats,
            'debt_ratio_series' => [
                'labels' => ['Lunas', 'Belum Lunas'],
                'data' => [$paidCount, $unpaidCount]
            ]
        ];
    }

    /**
     * INVENTORY HEALTH (Stok Tangki)
     */
    public function getInventoryHealth(): \Illuminate\Support\Collection
    {
        $products = Product::where('is_active', true)->get();
        $analysisStartDate = Carbon::now()->subDays(30);

        return $products->map(function ($p) use ($analysisStartDate) {
            // 1. Ambil Transaksi Valid 30 Hari Terakhir Khusus Produk Ini
            $transactions = Transaction::valid() // Pakai Scope Valid (DRY)
            ->where('transaction_date', '>=', $analysisStartDate)
                ->whereHas('items', fn($q) => $q->where('product_id', $p->id))
                ->with(['items' => fn($q) => $q->where('product_id', $p->id)])
                ->get();

            // 2. Hitung Total Volume Terjual
            $totalVolumeSold = $transactions->sum(fn($t) => $t->items->sum('quantity_liter'));

            // 3. Hitung "Active Trading Days" (Berapa hari SPBU benar-benar jualan produk ini)
            $activeDays = $transactions->groupBy(fn($t) => $t->transaction_date->format('Y-m-d'))->count();

            $avgDaily = 0;
            $estimationText = 'Belum ada transaksi';
            $status = 'safe';

            if ($activeDays > 0) {
                $avgDaily = $totalVolumeSold / $activeDays;

                if ($p->stock <= 0) {
                    $estimationText = 'Stok Habis';
                    $status = 'empty';
                } else {
                    $daysLeft = $p->stock / $avgDaily;

                    if ($daysLeft < 1) {
                        $hoursLeft = round($daysLeft * 24);
                        $estimationText = $hoursLeft > 0 ? "Kurang dari {$hoursLeft} Jam" : "Kritis (< 1 Jam)";
                        $status = 'critical';
                    } else {
                        $daysLeftRounded = round($daysLeft);
                        $estimationText = "± {$daysLeftRounded} Hari";

                        if ($daysLeft <= 3) $status = 'critical';
                        elseif ($daysLeft <= 7) $status = 'warning';
                        else $status = 'safe';
                    }
                }
            } else {
                if ($p->stock <= 0) {
                    $estimationText = 'Stok Kosong';
                    $status = 'empty';
                }
            }

            return [
                'id' => $p->id,
                'name' => $p->name,
                'stock' => $p->stock,
                'unit' => $p->unit,
                'days_to_empty' => $estimationText,
                'avg_daily' => round($avgDaily, 1),
                'active_days' => $activeDays,
                'status' => $status,
            ];
        });
    }

    /**
     * TABEL & LIST (Top Debitur, Transaksi Terakhir, Shift Aktif)
     */
    public function getOperationalLists(): array
    {
        // Top 5 Debitur (Piutang)
        $topDebtors = Transaction::valid()
            ->where('payment_status', PaymentStatusEnum::UNPAID)
            ->join('customers', 'transactions.customer_id', '=', 'customers.id')
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
                'customer' => $t->customer ? ($t->customer->owner_name ?? $t->customer->manager_name) : 'Umum',
                'ship_name' => $t->customer ? $t->customer->ship_name : null,
                'owner_name' => $t->customer ? $t->customer->owner_name : null,
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
