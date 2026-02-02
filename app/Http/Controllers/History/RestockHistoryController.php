<?php

namespace App\Http\Controllers\History;

use App\Http\Controllers\Controller;
use App\Http\Requests\History\UpdateRestockRequest;
use App\Models\Product;
use App\Models\Restock;
use App\Traits\ExportHelper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    /**
     * Update Restock (Owner Only)
     * Logic: Kembalikan stok lama -> Tambah stok baru
     */
    public function update(UpdateRestockRequest $request, string $id)
    {
        return DB::transaction(function () use ($request, $id) {
            $restock = Restock::findOrFail($id);

            // 1. Rollback Stok Lama (Kurangi stok produk asli sesuai volume lama)
            $oldProduct = Product::findOrFail($restock->product_id);
            $oldProduct->decrement('stock', $restock->volume_liter);

            // 2. Update Data Restock
            $restock->update([
                'date' => $request->date,
                'product_id' => $request->product_id,
                'volume_liter' => $request->volume_liter,
                'total_cost' => $request->total_cost,
                'note' => $request->note,
            ]);

            // 3. Apply Stok Baru (Tambah ke produk baru/sama)
            $newProduct = Product::findOrFail($request->product_id);
            $newProduct->increment('stock', $request->volume_liter);

            return redirect()->back()->with('success', 'Data Restock berhasil direvisi & Stok disesuaikan.');
        });
    }

}
