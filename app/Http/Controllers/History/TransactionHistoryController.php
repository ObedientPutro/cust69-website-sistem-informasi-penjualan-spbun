<?php

namespace App\Http\Controllers\History;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\TransactionService;
use Barryvdh\DomPDF\Facade\Pdf;
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

        $summary = [
            'total_omset' => (clone $query)->sum('grand_total'),
            'total_transaksi' => (clone $query)->count(),
        ];

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
        $data = $query->get();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.transactions_pdf', [
                'transactions' => $data,
                'period' => $filters['start_date'] . ' s/d ' . $filters['end_date']
            ])->setPaper('a4', 'landscape');

            return $pdf->download('Laporan_Transaksi_' . date('Ymd_His') . '.pdf');
        }

        // CSV Stream
        $fileName = 'Laporan_Transaksi_' . date('Ymd_His') . '.csv';
        return response()->streamDownload(function () use ($data) {
            $handle = fopen('php://output', 'w');

            // Header CSV
            fputcsv($handle, ['Tgl', 'Ref ID', 'Customer', 'Items', 'Total', 'Metode', 'Status', 'Kasir']);

            foreach ($data as $row) {
                // Format Items menjadi string: "Pertalite (2L), Solar (5L)"
                $itemsString = $row->items->map(fn($i) => $i->product->name . ' (' . $i->quantity_liter . 'L)')->join(', ');

                fputcsv($handle, [
                    $row->transaction_date->format('Y-m-d H:i'),
                    '#' . $row->id,
                    $row->customer ? $row->customer->name : 'Umum',
                    $itemsString,
                    $row->grand_total,
                    $row->payment_method->value,
                    $row->payment_status->value,
                    $row->user->name
                ]);
            }
            fclose($handle);
        }, $fileName);
    }
}
