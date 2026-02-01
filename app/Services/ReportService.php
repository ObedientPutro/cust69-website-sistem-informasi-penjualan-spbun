<?php

namespace App\Services;

use App\Enums\PaymentMethodEnum;
use App\Enums\PumpShiftStatusEnum;
use App\Models\PumpShift;
use App\Models\Restock;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * 1. Laporan Penjualan Harian (Rekonsiliasi Totalisator vs Sistem)
     */
    public function getDailySalesReport(string $start, string $end, ?string $productId = null)
    {
        // 1. Ambil Data Shift (Fisik / Laci)
        $shifts = PumpShift::with('product')
            ->whereBetween('date', [$start, $end])
            ->where('status', PumpShiftStatusEnum::CLOSED)
            ->when($productId, fn($q) => $q->where('product_id', $productId))
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });

        // 2. Ambil Data Transaksi (Sistem)
        $transactions = Transaction::with(['items.product', 'user', 'customer'])
            ->whereBetween('transaction_date', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->when($productId, function($q) use ($productId) {
                $q->whereHas('items', fn($i) => $i->where('product_id', $productId));
            })
            ->get()
            ->groupBy(fn($t) => $t->transaction_date->format('Y-m-d'));

        // 3. Gabungkan & Hitung Detail
        $report = [];
        $period = Carbon::parse($start)->daysUntil($end);

        foreach ($period as $date) {
            $dateStr = $date->format('Y-m-d');

            // Skip jika tanggal kosong (opsional, jika ingin tampil tanggal kosong, hapus baris ini)
            if (!isset($shifts[$dateStr]) && !isset($transactions[$dateStr])) continue;

            $dayShifts = $shifts[$dateStr] ?? collect([]);
            $dayTrans  = $transactions[$dateStr] ?? collect([]);

            // --- A. ANALISA FISIK (SHIFT) ---
            // 1. Liter Fisik (Totalisator)
            $totalizerLiter = $dayShifts->sum(function ($s) {
                // Pastikan tipe data float
                $savedLiter = (float) $s->total_sales_liter;
                $opening    = (float) $s->opening_totalizer;
                $closing    = (float) $s->closing_totalizer;

                // Jika data tersimpan ada, pakai itu.
                // Jika 0, coba hitung manual (Closing - Opening)
                if ($savedLiter > 0) {
                    return $savedLiter;
                }

                // Hitung manual jika closing valid (>0) dan lebih besar dari opening
                if ($closing > 0 && $closing >= $opening) {
                    return $closing - $opening;
                }

                return 0;
            });

            // 2. Uang Fisik (Laci)
            // Pastikan casting float agar tidak error jika null/string
            $physicalCash = $dayShifts->sum(fn($s) => (float) $s->cash_collected);

            // --- B. ANALISA SISTEM (TRANSAKSI) ---
            $systemLiter = $dayTrans->sum(fn($t) => $t->items->sum('quantity_liter'));

            // Breakdown Pembayaran Sistem
            $sysCash     = $dayTrans->where('payment_method', PaymentMethodEnum::CASH)->sum('grand_total');
            $sysTransfer = $dayTrans->where('payment_method', PaymentMethodEnum::TRANSFER)->sum('grand_total');
            $sysBon      = $dayTrans->where('payment_method', PaymentMethodEnum::BON)->sum('grand_total');
            $sysTotal    = $sysCash + $sysTransfer + $sysBon;

            // --- C. SELISIH (DISCREPANCY) ---
            $diffLiter = $systemLiter - $totalizerLiter; // (+ Sistem lebih besar, - Mesin lebih besar)
            $diffCash  = $physicalCash - $sysCash;       // Uang Laci - Uang Cash Seharusnya

            // Status Liter
            $statusLiter = 'match';
            if ($diffLiter < 0 && $diffLiter >= -2) $statusLiter = 'warning';
            if ($diffLiter < -2 || $diffLiter > 5) $statusLiter = 'danger';

            $report[] = [
                'date' => $dateStr,

                // Liter
                'phys_liter' => $totalizerLiter,
                'sys_liter'  => $systemLiter,
                'diff_liter' => $diffLiter,
                'status'     => $statusLiter,

                // Money Breakdown
                'sys_cash'     => $sysCash,
                'sys_transfer' => $sysTransfer,
                'sys_bon'      => $sysBon,
                'sys_total'    => $sysTotal,

                // Physical Cash Check
                'phys_cash' => $physicalCash,
                'diff_cash' => $diffCash,

                // Detail (Untuk Expand)
                'shifts' => $dayShifts,
                'transactions' => $dayTrans
            ];
        }

        return collect($report)->sortByDesc('date')->values();
    }

    /**
     * 2. Laporan Arus Stok (Masuk vs Keluar)
     */
    public function getStockFlowReport(string $start, string $end, ?string $productId = null)
    {
        // A. Barang Masuk (Restock)
        $inflow = Restock::with('product')
            ->whereBetween('date', [$start, $end])
            ->when($productId, fn($q) => $q->where('product_id', $productId))
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date),
                    'type' => 'Masuk (DO)',
                    'product_name' => $item->product->name,
                    'qty_in' => $item->volume_liter,
                    'qty_out' => 0,
                    'ref' => $item->note ?? 'DO #' . $item->id,
                ];
            });

        // B. Barang Keluar (Penjualan)
        $outflow = TransactionItem::with(['transaction', 'product'])
            ->whereHas('transaction', function ($q) use ($start, $end) {
                $q->whereBetween('transaction_date', [$start . ' 00:00:00', $end . ' 23:59:59']);
            })
            ->when($productId, fn($q) => $q->where('product_id', $productId))
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->transaction->transaction_date,
                    'type' => 'Keluar (Jual)',
                    'product_name' => $item->product->name,
                    'qty_in' => 0,
                    'qty_out' => $item->quantity_liter,
                    'ref' => 'TRX #' . $item->transaction->id,
                ];
            });

        return $inflow->merge($outflow)->sortByDesc('date')->values();
    }

    /**
     * 3. Laporan Laba Rugi
     */
    public function getProfitLossReport(string $start, string $end, ?string $productId = null)
    {
        $query = TransactionItem::whereHas('transaction', function ($q) use ($start, $end) {
            $q->whereBetween('transaction_date', [$start . ' 00:00:00', $end . ' 23:59:59']);
        });

        if ($productId) {
            $query->where('product_id', $productId);
        }

        $data = $query->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->select(
                DB::raw('DATE(transactions.transaction_date) as date'),
                DB::raw('SUM(transaction_items.subtotal) as omzet'),
                DB::raw('SUM(transaction_items.quantity_liter * transaction_items.cost_per_liter) as hpp')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($row) {
                return [
                    'date' => $row->date,
                    'omzet' => (float) $row->omzet,
                    'hpp' => (float) $row->hpp,
                    'gross_profit' => (float) $row->omzet - (float) $row->hpp,
                ];
            });

        return $data;
    }
}
