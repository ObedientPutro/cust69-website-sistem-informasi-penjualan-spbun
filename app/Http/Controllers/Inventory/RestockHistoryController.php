<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Restock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class RestockHistoryController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->endOfMonth()->toDateString());
        $productId = $request->input('product_id');
        $search = $request->input('search');

        $query = Restock::with(['product', 'user'])
            ->whereBetween('date', [$startDate, $endDate]);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('note', 'like', "%{$search}%") // Cari No DO
                ->orWhereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%"); // Cari Nama Produk
                })
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%"); // Cari Admin
                    });
            });
        }

        $summary = [
            'total_volume' => $query->sum('volume_liter'),
            'total_cost' => $query->sum('total_cost'),
        ];

        $logs = $query->latest('date')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('History/RestockHistory', [
            'logs' => $logs,
            'summary' => $summary,
            'products' => Product::select('id', 'name')->orderBy('name')->get(),
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'product_id' => $productId ?? '',
                'search' => $search,
            ]
        ]);
    }

    public function export(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $productId = $request->input('product_id');
        $format = $request->input('format', 'csv');

        $query = Restock::with(['product', 'user'])->whereBetween('date', [$startDate, $endDate]);
        if ($productId) $query->where('product_id', $productId);

        if ($format === 'pdf') {
            $data = $query->orderBy('date', 'asc')->get(); // Get all data
            $pdf = Pdf::loadView('exports.restocks_pdf', [
                'logs' => $data,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            return $pdf->download("Laporan_Restock_{$startDate}.pdf");
        }

        $fileName = "Restock_DO_{$startDate}_{$endDate}.csv";

        return response()->streamDownload(function () use ($startDate, $endDate, $productId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Tanggal', 'No. DO / Ref', 'Produk', 'Volume (L)', 'Total Harga (Rp)', 'Admin']);

            $query = Restock::with(['product', 'user'])->whereBetween('date', [$startDate, $endDate]);
            if ($productId) $query->where('product_id', $productId);

            $query->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->date->format('Y-m-d'),
                        $row->note ?? '-',
                        $row->product->name,
                        $row->volume_liter,
                        $row->total_cost,
                        $row->user->name
                    ]);
                }
            });
            fclose($handle);
        }, $fileName);
    }
}
