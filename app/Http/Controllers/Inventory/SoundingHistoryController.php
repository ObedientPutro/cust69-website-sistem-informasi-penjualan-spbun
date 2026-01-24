<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\TankSounding;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SoundingHistoryController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', Carbon::today()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::today()->endOfMonth()->toDateString());
        $productId = $request->input('product_id');
        $search = $request->input('search');

        $query = TankSounding::with(['product', 'user'])
            ->whereBetween('recorded_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query->latest('recorded_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('History/SoundingHistory', [
            'logs' => $logs,
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

        $query = TankSounding::with(['product', 'user'])
            ->whereBetween('recorded_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        if ($productId) $query->where('product_id', $productId);

        if ($format === 'pdf') {
            $data = $query->orderBy('recorded_at', 'asc')->get();
            $pdf = Pdf::loadView('exports.soundings_pdf', [
                'logs' => $data,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            return $pdf->download("Laporan_Audit_{$startDate}.pdf");
        }

        $fileName = "Audit_Tangki_{$startDate}_{$endDate}.csv";

        return response()->streamDownload(function () use ($startDate, $endDate, $productId) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Waktu Cek', 'Produk', 'Tinggi (cm)', 'Stok Sistem (L)', 'Stok Fisik (L)', 'Selisih (L)', 'Petugas']);

            $query = TankSounding::with(['product', 'user'])
                ->whereBetween('recorded_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

            if ($productId) $query->where('product_id', $productId);

            $query->chunk(200, function ($rows) use ($handle) {
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row->recorded_at->format('Y-m-d H:i'),
                        $row->product->name,
                        $row->physical_height_cm ?? '-',
                        $row->system_liter_snapshot,
                        $row->physical_liter,
                        $row->difference,
                        $row->user->name
                    ]);
                }
            });
            fclose($handle);
        }, $fileName);
    }
}
