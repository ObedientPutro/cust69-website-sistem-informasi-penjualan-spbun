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
     * Laporan Penjualan Harian (Rekonsiliasi Totalisator vs Sistem)
     */
    public function getDailySalesReport(string $start, string $end, ?string $productId = null)
    {
        // 1. Ambil Data Shift (Fisik / Laci / Snapshot Sistem)
        $shifts = PumpShift::with('product')
            ->whereBetween('date', [$start, $end])
            ->where('status', PumpShiftStatusEnum::CLOSED)
            ->when($productId, fn($q) => $q->where('product_id', $productId))
            ->get()
            ->groupBy(function ($item) {
                return Carbon::parse($item->date)->format('Y-m-d');
            });

        // 2. Ambil Data Transaksi (Sistem Dinamis - Untuk Breakdown Omset)
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

            if (!$shifts->has($dateStr) && !$transactions->has($dateStr)) continue;

            $dayShifts = $shifts->get($dateStr, collect([]));
            $dayTrans  = $transactions->get($dateStr, collect([]));

            // --- A. ANALISA VOLUME (STRICT SNAPSHOT) ---
            // Fisik Mesin (Totalisator)
            $totalizerLiter = $dayShifts->sum(fn($s) => (float) $s->total_sales_liter);

            // Sistem Snapshot (Angka sistem saat shift ditutup)
            $systemLiterSnapshot = $dayShifts->sum(fn($s) => (float) $s->system_transaction_liter);

            // Selisih Liter (Berdasarkan snapshot saat itu)
            $diffLiter = $systemLiterSnapshot - $totalizerLiter;


            // --- B. ANALISA KEUANGAN (DINAMIS & HYBRID) ---
            // 1. Filter Transaksi Valid (Buang yang Returned)
            $validTrans = $dayTrans->where('payment_status', '!=', PaymentStatusEnum::RETURNED);

            // 2. Omset Breakdown (Dinamis - Agar update jika ada edit transaksi)
            $sysCash     = $validTrans->where('payment_method', PaymentMethodEnum::CASH)->sum(fn($t) => (float)$t->grand_total);
            $sysTransfer = $validTrans->where('payment_method', PaymentMethodEnum::TRANSFER)->sum(fn($t) => (float)$t->grand_total);
            $sysBon      = $validTrans->where('payment_method', PaymentMethodEnum::BON)->sum(fn($t) => (float)$t->grand_total);
            $sysTotal    = $sysCash + $sysTransfer + $sysBon;

            $sysBackdate = $validTrans->where('is_backdate', true)->sum(fn($t) => (float)$t->grand_total);

            $sysCashTarget = $validTrans
                ->where('payment_method', PaymentMethodEnum::CASH)
                ->where('is_backdate', false)
                ->sum(fn($t) => (float)$t->grand_total);

            // 3. Cash Control (Beda Kas)
            // Uang Fisik: Ambil dari Snapshot Shift (Uang di Laci)
            $physicalCash = $dayShifts->sum(fn($s) => (float) $s->cash_collected);
            $diffCash  = $physicalCash - $sysCashTarget;

            $report[] = [
                'date' => $dateStr,

                // Volume Columns (Strict Snapshot)
                'phys_liter' => $totalizerLiter,
                'sys_liter'  => $systemLiterSnapshot, // Menggunakan Snapshot
                'diff_liter' => $diffLiter,           // Selisih Snapshot

                // Money Columns (Dynamic)
                'sys_cash'     => $sysCashTarget,
                'sys_transfer' => $sysTransfer,
                'sys_bon'      => $sysBon,
                'sys_total'    => $sysTotal,
                'sys_backdate' => $sysBackdate,

                // Cash Reconciliation (Hybrid)
                'phys_cash' => $physicalCash, // Snapshot
                'diff_cash' => $diffCash,     // Hybrid Calculation

                // Raw Data for Detail View
                'shifts' => $dayShifts,
                'transactions' => $dayTrans
            ];
        }

        return collect($report)->sortByDesc('date')->values();
    }

    /**
     * Laporan Arus Stok (Masuk vs Keluar)
     */
    public function getStockFlowReport(string $start, string $end, ?string $productId = null): \Illuminate\Support\Collection
    {
        $inflow = Restock::with('product')
            ->whereBetween('date', [$start, $end])
            ->when($productId, fn($q) => $q->where('product_id', $productId))
            ->get()
            ->toBase()
            ->map(function ($item) {
                return [
                    'sort_date' => Carbon::parse($item->date)->startOfDay(),
                    'date' => $item->date,
                    'type' => 'Restock (DO)',
                    'product_name' => $item->product->name,
                    'customer_name' => '-',
                    'qty_in' => (float) $item->volume_liter,
                    'qty_out' => 0,
                    'ref' => $item->note ?? '-',
                ];
            });

        $outflow = TransactionItem::with(['transaction.customer', 'product'])
            ->whereHas('transaction', function ($q) use ($start, $end) {
                $q->valid()
                    ->whereNotNull('pump_shift_id')
                    ->whereBetween('transaction_date', [$start . ' 00:00:00', $end . ' 23:59:59']);
            })
            ->when($productId, fn($q) => $q->where('product_id', $productId))
            ->get()
            ->toBase()
            ->map(function ($item) {
                $trx = $item->transaction;
                $custName = 'Umum';

                if ($trx->customer) {
                    $custName = $trx->customer->ship_name ?? $trx->customer->owner_name;
                }

                return [
                    'sort_date' => $trx->transaction_date,
                    'date' => $trx->transaction_date->format('Y-m-d H:i'),
                    'type' => 'Penjualan',
                    'product_name' => $item->product->name,
                    'customer_name' => $custName,
                    'qty_in' => 0,
                    'qty_out' => (float) $item->quantity_liter,
                    'ref' => $trx->trx_code,
                ];
            });

        $inflowBase = $inflow->toBase();
        $outflowBase = $outflow->toBase();

        return $inflowBase->merge($outflowBase)->sortByDesc('sort_date')->values();
    }

    /**
     * Laporan Laba Rugi
     */
    public function getProfitLossReport(string $start, string $end, ?string $productId = null): \Illuminate\Support\Collection
    {
        $query = TransactionItem::whereHas('transaction', function ($q) use ($start, $end) {
            $q->valid()->whereBetween('transaction_date', [$start . ' 00:00:00', $end . ' 23:59:59']);
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
