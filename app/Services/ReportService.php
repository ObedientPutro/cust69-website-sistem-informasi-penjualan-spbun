<?php

namespace App\Services;

use App\Models\Restock;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * 1. Laporan Penjualan (Detail per Nota)
     */
    public function getSalesReport(string $start, string $end)
    {
        return Transaction::with(['user', 'customer', 'items.product'])
            ->whereBetween('transaction_date', [$start . ' 00:00:00', $end . ' 23:59:59'])
            ->latest('transaction_date')
            ->get();
    }

    /**
     * 2. Laporan Arus Stok (Masuk vs Keluar)
     * Menggabungkan data Restock (Masuk) dan Penjualan (Keluar)
     */
    public function getStockFlowReport(string $start, string $end)
    {
        // A. Barang Masuk (Restock)
        // Pastikan Model Restock ada, jika belum, ganti dengan DB::table('restocks')
        $inflow = Restock::with('product')
            ->whereBetween('date', [$start, $end])
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

        // C. Gabung & Sortir
        return $inflow->merge($outflow)->sortByDesc('date')->values();
    }

    /**
     * 3. Laporan Laba Rugi (Profit & Loss)
     * Grouping per Hari
     */
    public function getProfitLossReport(string $start, string $end)
    {
        $data = TransactionItem::whereHas('transaction', function ($q) use ($start, $end) {
            $q->whereBetween('transaction_date', [$start . ' 00:00:00', $end . ' 23:59:59']);
        })
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
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
