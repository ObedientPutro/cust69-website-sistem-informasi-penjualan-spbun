<?php

namespace App\Http\Controllers\History;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\TankSounding;
use App\Traits\ExportHelper;
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

        if ($request->has('sort') && $request->has('direction')) {
            $query->orderBy($request->sort, $request->direction);
        } else {
            $query->latest('recorded_at');
        }

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

        if ($request->has('sort') && $request->has('direction')) {
            $query->orderBy($request->sort, $request->direction);
        } else {
            $query->orderBy('recorded_at', 'asc');
        }

        $period = $startDate . ' s/d ' . $endDate;

        if ($format == 'pdf') {
            return ExportHelper::toPdf(
                'exports.histories.soundings_pdf',
                ['logs' => $query->get(), 'title' => 'Laporan Audit Tangki (Sounding)', 'period' => $period],
                "Laporan_Audit_{$startDate}.pdf"
            );
        }

        return ExportHelper::toCsv(
            "Audit_Tangki_{$startDate}.csv",
            ['Waktu Cek', 'Produk', 'Tinggi (cm)', 'Stok Sistem (L)', 'Stok Fisik (L)', 'Selisih (L)', 'Petugas'],
            $query,
            function ($row) {
                return [
                    $row->recorded_at->format('Y-m-d H:i'),
                    $row->product->name,
                    $row->physical_height_cm ?? '-',
                    $row->system_liter_snapshot,
                    $row->physical_liter,
                    $row->difference,
                    $row->user->name
                ];
            }
        );
    }


}
