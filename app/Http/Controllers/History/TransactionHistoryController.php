<?php

namespace App\Http\Controllers\History;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\TransactionService;
use App\Traits\ExportHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionHistoryController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $filters = [
            'start_date' => $startDate ?: Carbon::now()->startOfMonth()->toDateString(),
            'end_date'   => $endDate ?: Carbon::now()->endOfMonth()->toDateString(),
            'search'     => $request->input('search'),
            'product_id' => $request->input('product_id'),
            'payment_status' => $request->input('payment_status'),
            'payment_method' => $request->input('payment_method'),
        ];

        $query = $this->transactionService->getHistory($filters);

        $baseQuery = clone $query;
        $totalCount = (clone $baseQuery)->count();
        $totalOmset = (clone $baseQuery)
            ->where('payment_status', '!=', 'returned')
            ->sum('grand_total');

        $voidQuery = (clone $baseQuery)->where('payment_status', 'returned');
        $totalVoidCount = $voidQuery->count();
        $totalVoidAmount = $voidQuery->sum('grand_total');

        $summary = [
            'total_transaksi' => $totalCount,
            'total_omset' => $totalOmset,
            'total_void_count' => $totalVoidCount,
            'total_void_amount' => $totalVoidAmount,
        ];

        if ($request->filled('sort') && $request->filled('direction')) {
            $query->orderBy($request->input('sort'), $request->input('direction'));
        } else {
            $query->latest('transaction_date');
        }

        return Inertia::render('History/TransactionHistory', [
            'transactions' => $query->paginate(15)->withQueryString(),
            'products' => Product::select('id', 'name')->orderBy('name')->get(),
            'filters' => $filters,
            'summary' => $summary
        ]);
    }

    public function export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $filters = [
            'start_date' => $startDate ?: Carbon::now()->startOfMonth()->toDateString(),
            'end_date'   => $endDate   ?: Carbon::now()->endOfMonth()->toDateString(),
            'search'     => $request->input('search'),
            'product_id' => $request->input('product_id'),
            'payment_status' => $request->input('payment_status'),
            'payment_method' => $request->input('payment_method'),
        ];

        $format = $request->input('format', 'csv');

        $query = $this->transactionService->getHistory($filters);

        if ($request->has('sort') && $request->has('direction')) {
            $query->orderBy($request->sort, $request->direction);
        } else {
            $query->orderBy('transaction_date', 'asc');
        }

        $period = $filters['start_date'] . ' s/d ' . $filters['end_date'];

        if ($format == 'pdf') {
            return ExportHelper::toPdf(
                'exports.histories.transactions_pdf',
                ['transactions' => $query->get(), 'title' => 'Laporan Transaksi Penjualan', 'period' => $period],
                'Laporan_Transaksi_' . date('Ymd_His') . '.pdf'
            );
        }

        return ExportHelper::toCsv(
            'Laporan_Transaksi_' . date('Ymd_His') . '.csv',
            ['Tanggal', 'Waktu', 'No Nota', 'Pelanggan / Kapal', 'Item Produk', 'Total (Rp)', 'Metode', 'Status', 'Kasir'],
            $query,
            function ($row) {
                $customerName = 'Umum';
                if ($row->customer) {
                    $customerName = $row->customer->ship_name ?? $row->customer->owner_name;
                }

                $itemsString = $row->items->map(fn($i) =>
                    $i->product->name . ' (' . (float)$i->quantity_liter . 'L)'
                )->join(', ');

                return [
                    $row->transaction_date->format('Y-m-d'),
                    $row->transaction_date->format('H:i'),
                    $row->trx_code ?? ('#' . $row->id),
                    $customerName,
                    $itemsString,
                    $row->grand_total,
                    ucfirst($row->payment_method->value),
                    ucfirst($row->payment_status->value),
                    $row->user->name
                ];
            }
        );
    }


}
