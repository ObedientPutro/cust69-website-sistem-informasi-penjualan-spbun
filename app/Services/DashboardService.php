<?php

namespace App\Services;

use App\Enums\PaymentStatusEnum;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * 1. FINANCIAL HEALTH (Kesehatan Keuangan)
     */
    public function getFinancialStats()
    {
        $startMonth = Carbon::now()->startOfMonth();
        $endMonth = Carbon::now()->endOfMonth();
        $today = Carbon::today();

        // A. Laba Kotor Estimasi Hari Ini (Gross Profit)
        // Rumus: (Harga Jual - HPP) * Qty
        $todayProfit = TransactionItem::whereHas('transaction', function ($q) use ($today) {
            $q->whereDate('transaction_date', $today);
        })
            ->get()
            ->sum(function ($item) {
                return ($item->price_per_liter - $item->cost_per_liter) * $item->quantity_liter;
            });

        // B. Total Piutang Aktif (Resiko Macet)
        $totalDebt = Transaction::where('payment_status', PaymentStatusEnum::UNPAID)->sum('grand_total');

        // C. Omzet Bulan Ini
        $monthlyRevenue = Transaction::whereBetween('transaction_date', [$startMonth, $endMonth])->sum('grand_total');

        // D. Rasio Bon vs Cash Bulan Ini (Untuk Cashflow Analysis)
        $trxMonth = Transaction::whereBetween('transaction_date', [$startMonth, $endMonth])->get();
        $cashTotal = $trxMonth->where('payment_method', '!=', 'bon')->sum('grand_total');
        $bonTotal = $trxMonth->where('payment_method', '==', 'bon')->sum('grand_total');
        $total = $cashTotal + $bonTotal;
        $bonRatio = $total > 0 ? round(($bonTotal / $total) * 100, 1) : 0;

        return [
            'today_profit' => $todayProfit,
            'today_revenue' => Transaction::whereDate('transaction_date', $today)->sum('grand_total'),
            'total_debt' => $totalDebt,
            'monthly_revenue' => $monthlyRevenue,
            'bon_ratio' => $bonRatio // Alert jika > 50%
        ];
    }

    /**
     * 2. INVENTORY INTELLIGENCE (Analisis Stok)
     */
    public function getStockAnalysis()
    {
        // Ambil rata-rata penjualan 7 hari terakhir untuk estimasi habis
        $last7Days = Carbon::now()->subDays(7);

        return Product::where('is_active', true)
            ->withCount(['transactionItems as avg_daily_sales' => function($query) use ($last7Days) {
                $query->where('created_at', '>=', $last7Days)
                    ->select(DB::raw('SUM(quantity_liter) / 7'));
            }])
            ->get()
            ->map(function ($product) {
                // Hitung Days to Empty (Ketahanan Stok)
                $avgSales = $product->avg_daily_sales ?: 1; // Hindari division by zero
                $daysToEmpty = round($product->stock / $avgSales);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'stock' => $product->stock,
                    'unit' => $product->unit,
                    'days_to_empty' => $daysToEmpty,
                    'status' => $daysToEmpty < 3 ? 'critical' : ($daysToEmpty < 7 ? 'warning' : 'safe'),
                    'avg_sales' => round($avgSales, 1)
                ];
            });
    }

    /**
     * 3. MARKET TRENDS (Grafik Penjualan per Produk)
     */
    public function getProductSalesTrend()
    {
        $start = Carbon::now()->subDays(14); // 2 Minggu terakhir
        $end = Carbon::now();
        $period = CarbonPeriod::create($start, $end);

        $products = Product::where('is_active', true)->get();
        $series = [];
        $categories = [];

        foreach ($period as $date) {
            $categories[] = $date->format('d M');
        }

        foreach ($products as $product) {
            $data = [];
            foreach ($period as $date) {
                $qty = TransactionItem::where('product_id', $product->id)
                    ->whereHas('transaction', function($q) use ($date) {
                        $q->whereDate('transaction_date', $date);
                    })
                    ->sum('quantity_liter');
                $data[] = $qty;
            }
            $series[] = [
                'name' => $product->name,
                'data' => $data
            ];
        }

        return [
            'categories' => $categories,
            'series' => $series
        ];
    }

    /**
     * 4. ACTIONABLE LISTS (Top Debtors & Top Customers)
     */
    public function getTopLists()
    {
        // 5 Penunggak Terbesar
        $topDebtors = Transaction::where('payment_status', PaymentStatusEnum::UNPAID)
            ->join('customers', 'transactions.customer_id', '=', 'customers.id')
            ->select('customers.name', 'customers.ship_name', DB::raw('SUM(grand_total) as total_debt'), DB::raw('COUNT(transactions.id) as bon_count'))
            ->groupBy('customers.id', 'customers.name', 'customers.ship_name')
            ->orderByDesc('total_debt')
            ->limit(5)
            ->get();

        return ['debtors' => $topDebtors];
    }
}
