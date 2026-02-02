<?php

namespace App\Services;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
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
        // REVISI: KITA AMBIL SEMUA (TERMASUK RETURNED) untuk keperluan pencocokan Liter
        $transactions = Transaction::with(['items.product', 'user', 'customer'])
            ->whereBetween('transaction_date', [$start . ' 00:00:00', $end . ' 23:59:59'])
            // HAPUS filter exclude 'returned' disini agar liter return tetap terambil
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

            if (!$shifts->has($dateStr) && !$transactions->has($dateStr)) continue;

            $dayShifts = $shifts->get($dateStr, collect([]));
            $dayTrans  = $transactions->get($dateStr, collect([]));

            // --- A. ANALISA FISIK (SHIFT) ---
            $totalizerLiter = $dayShifts->sum(function ($s) {
                return (float) $s->total_sales_liter;
            });

            $physicalCash = $dayShifts->sum(fn($s) => (float) $s->cash_collected);

            // --- B. ANALISA SISTEM ---

            // 1. Liter: Hitung SEMUA (termasuk Return) agar match dengan Totalisator
            $systemLiter = $dayTrans->sum(fn($t) => $t->items->sum('quantity_liter'));

            // 2. Uang: HANYA hitung yang VALID (Bukan Return)
            $validTrans = $dayTrans->where('payment_status', '!=', PaymentStatusEnum::RETURNED);

            $sysCash     = $validTrans->where('payment_method', PaymentMethodEnum::CASH)->sum(fn($t) => (float)$t->grand_total);
            $sysTransfer = $validTrans->where('payment_method', PaymentMethodEnum::TRANSFER)->sum(fn($t) => (float)$t->grand_total);
            $sysBon      = $validTrans->where('payment_method', PaymentMethodEnum::BON)->sum(fn($t) => (float)$t->grand_total);
            $sysTotal    = $sysCash + $sysTransfer + $sysBon;

            // --- C. SELISIH ---
            // systemLiter (Gross) dikurang totalizerLiter
            // Jika Return dihitung di systemLiter, maka selisihnya akan mendekati 0 (Match), meskipun uangnya 0.
            $diffLiter = $systemLiter - $totalizerLiter;
            $diffCash  = $physicalCash - $sysCash;

            $report[] = [
                'date' => $dateStr,
                'phys_liter' => $totalizerLiter,
                'sys_liter'  => $systemLiter,
                'diff_liter' => $diffLiter,
                'sys_cash'     => $sysCash,
                'sys_transfer' => $sysTransfer,
                'sys_bon'      => $sysBon,
                'sys_total'    => $sysTotal, // Total Uang Bersih
                'phys_cash' => $physicalCash,
                'diff_cash' => $diffCash,
                'shifts' => $dayShifts,
                'transactions' => $dayTrans // Kirim semua data agar bisa diloop di detail (termasuk yg returned)
            ];
        }

        return collect($report)->sortByDesc('date')->values();
    }

    /**
     * 2. Laporan Arus Stok (Masuk vs Keluar)
     */
    public function getStockFlowReport(string $start, string $end, ?string $productId = null)
    {
        $inflow = Restock::with('product')
            ->whereBetween('date', [$start, $end])
            ->when($productId, fn($q) => $q->where('product_id', $productId))
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'type' => 'Restock (DO)',
                    'product_name' => $item->product->name,
                    'qty_in' => $item->volume_liter,
                    'qty_out' => 0,
                    'ref' => $item->note ?? '-',
                ];
            });

        $outflow = TransactionItem::with(['transaction', 'product'])
            ->whereHas('transaction', function ($q) use ($start, $end) {
                $q->whereBetween('transaction_date', [$start . ' 00:00:00', $end . ' 23:59:59'])
                    ->where('payment_status', '!=', PaymentStatusEnum::RETURNED->value); // Tetap Exclude
            })
            ->when($productId, fn($q) => $q->where('product_id', $productId))
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->transaction->transaction_date,
                    'type' => 'Penjualan',
                    'product_name' => $item->product->name,
                    'qty_in' => 0,
                    'qty_out' => $item->quantity_liter,
                    'ref' => $item->transaction->trx_code,
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
            $q->whereBetween('transaction_date', [$start . ' 00:00:00', $end . ' 23:59:59'])
                ->where('payment_status', '!=', PaymentStatusEnum::RETURNED->value); // Tetap Exclude
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
                    'gross_profit' => (float) $row->omzet - (float) $row->hpp
                ];
            });

        return $data;
    }
}
