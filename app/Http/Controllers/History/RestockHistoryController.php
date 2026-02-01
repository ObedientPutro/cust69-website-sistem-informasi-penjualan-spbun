<?php

namespace App\Http\Controllers\History;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Restock;
use App\Traits\ExportHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

        if ($request->has('sort') && $request->has('direction')) {
            $query->orderBy($request->sort, $request->direction);
        } else {
            $query->latest('date');
        }

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

        if ($request->has('sort') && $request->has('direction')) {
            $query->orderBy($request->sort, $request->direction);
        } else {
            $query->orderBy('date', 'asc');
        }

        $period = $startDate . ' s/d ' . $endDate;

        if ($format == 'pdf') {
            return ExportHelper::toPdf(
                'exports.histories.restocks_pdf',
                ['logs' => $query->get(), 'title' => 'Laporan Penebusan DO (Restock)', 'period' => $period],
                "Laporan_Restock_{$startDate}.pdf"
            );
        }

        return ExportHelper::toCsv(
            "Restock_DO_{$startDate}.csv",
            ['Tanggal', 'No. DO / Ref', 'Produk', 'Volume (L)', 'Total Harga (Rp)', 'Admin'],
            $query,
            function ($row) { // Mapper Function
                return [
                    $row->date->format('Y-m-d'),
                    $row->note ?? '-',
                    $row->product->name,
                    $row->volume_liter,
                    $row->total_cost,
                    $row->user->name
                ];
            }
        );
    }


}
