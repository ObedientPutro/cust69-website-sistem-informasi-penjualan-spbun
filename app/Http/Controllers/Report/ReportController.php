<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $service
    ) {}

    // --- 1. LAPORAN PENJUALAN ---
    public function sales(Request $request)
    {
        $start = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $end = $request->input('end_date', Carbon::now()->toDateString());

        return Inertia::render('Report/Sales', [
            'data' => $this->service->getSalesReport($start, $end),
            'filters' => ['start_date' => $start, 'end_date' => $end]
        ]);
    }

    // --- 2. LAPORAN ARUS STOK ---
    public function stock(Request $request)
    {
        $start = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $end = $request->input('end_date', Carbon::now()->toDateString());

        return Inertia::render('Report/Stock', [
            'data' => $this->service->getStockFlowReport($start, $end),
            'filters' => ['start_date' => $start, 'end_date' => $end]
        ]);
    }

    // --- 3. LAPORAN LABA RUGI (OWNER ONLY) ---
    public function profit(Request $request)
    {
        // Gate Authorization (Middleware juga bisa, tapi double check disini bagus)
        if (auth()->user()->role !== 'owner') abort(403);

        $start = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $end = $request->input('end_date', Carbon::now()->toDateString());

        return Inertia::render('Report/Profit', [
            'data' => $this->service->getProfitLossReport($start, $end),
            'filters' => ['start_date' => $start, 'end_date' => $end]
        ]);
    }

    // --- EXPORT HANDLER (Unified) ---
    public function export(Request $request, string $type)
    {
        $start = $request->input('start_date');
        $end = $request->input('end_date');
        $format = $request->input('format', 'pdf');

        $data = match($type) {
            'sales' => $this->service->getSalesReport($start, $end),
            'stock' => $this->service->getStockFlowReport($start, $end),
            'profit' => $this->service->getProfitLossReport($start, $end),
            default => abort(404)
        };

        if ($format === 'pdf') {
            $pdf = Pdf::loadView("exports.{$type}_pdf", [
                'data' => $data,
                'period' => "$start s/d $end",
                'user' => auth()->user()->name
            ])->setPaper('a4', $type === 'sales' ? 'landscape' : 'portrait');

            return $pdf->download("Laporan_{$type}_{$start}.pdf");
        }

        // CSV Export Logic (Sederhana)
        return response()->streamDownload(function() use ($data, $type) {
            $handle = fopen('php://output', 'w');
            // Logic CSV Header & Row beda-beda tiap tipe, disederhanakan disini:
            // (Implementasi detail CSV bisa ditambahkan jika perlu)
            fputcsv($handle, ['Data Export', $type]);
            fclose($handle);
        }, "Laporan_{$type}.csv");
    }
}
