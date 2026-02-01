<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ReportService;
use App\Traits\ExportHelper;
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
        $productId = $request->input('product_id');

        return Inertia::render('Report/Sales', [
            'data' => $this->service->getDailySalesReport($start, $end, $productId),
            'products' => Product::select('id', 'name')->orderBy('name')->get(),
            'filters' => ['start_date' => $start, 'end_date' => $end, 'product_id' => $productId]
        ]);
    }

    // --- 2. LAPORAN ARUS STOK ---
    public function stock(Request $request)
    {
        $start = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $end = $request->input('end_date', Carbon::now()->toDateString());
        $productId = $request->input('product_id');

        return Inertia::render('Report/Stock', [
            'data' => $this->service->getStockFlowReport($start, $end, $productId),
            'products' => Product::select('id', 'name')->orderBy('name')->get(),
            'filters' => ['start_date' => $start, 'end_date' => $end, 'product_id' => $productId]
        ]);
    }

    // --- 3. LAPORAN LABA RUGI (OWNER ONLY) ---
    public function profit(Request $request)
    {
        $start = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $end = $request->input('end_date', Carbon::now()->toDateString());
        $productId = $request->input('product_id');

        return Inertia::render('Report/Profit', [
            'data' => $this->service->getProfitLossReport($start, $end, $productId),
            'products' => Product::select('id', 'name')->orderBy('name')->get(),
            'filters' => ['start_date' => $start, 'end_date' => $end, 'product_id' => $productId]
        ]);
    }

    // --- EXPORT HANDLER (Unified) ---
    public function export(Request $request, string $type)
    {
        $start = $request->input('start_date');
        $end = $request->input('end_date');
        $productId = $request->input('product_id'); // Jika 'Semua', ini akan null/empty string
        $format = $request->input('format', 'pdf');
        $period = "$start s/d $end";

        // SALES EXPORT LOGIC
        if ($type === 'sales') {
            $data = $this->service->getDailySalesReport($start, $end, $productId);
            $title = 'Laporan Detail Penjualan & Setoran';

            if ($format === 'pdf') {
                return ExportHelper::toPdf('exports.reports.sales_pdf',
                    ['data' => $data, 'title' => $title, 'period' => $period],
                    "Sales_$start.pdf"
                );
            }

            return ExportHelper::toCsv(
                "Sales_Detail_$start.csv",
                [
                    'Tanggal', 'Fisik Liter (Mesin)', 'Sistem Liter', 'Selisih Liter',
                    'Omset Cash', 'Omset Transfer', 'Omset Bon', 'Total Omset',
                    'Uang Fisik (Laci)', 'Selisih Uang'
                ],
                $data,
                function ($row) {
                    return [
                        $row['date'],
                        $row['phys_liter'],
                        $row['sys_liter'],
                        $row['diff_liter'],
                        $row['sys_cash'],
                        $row['sys_transfer'],
                        $row['sys_bon'],
                        $row['sys_total'],
                        $row['phys_cash'],
                        $row['diff_cash']
                    ];
                }
            );
        }

        // STOCK EXPORT LOGIC
        if ($type === 'stock') {
            $data = $this->service->getStockFlowReport($start, $end, $productId);
            if ($format === 'pdf') {
                return ExportHelper::toPdf('exports.reports.stock_pdf', ['data' => $data, 'title' => 'Laporan Arus Stok', 'period' => $period], "Stock_$start.pdf");
            }

            return ExportHelper::toCsv(
                "Stock_$start.csv",
                ['Tanggal', 'Tipe', 'Produk', 'Ref', 'Masuk', 'Keluar'],
                $data,
                fn($row) => [
                    $row['date']->format('Y-m-d H:i'),
                    $row['type'],
                    $row['product_name'],
                    $row['ref'],
                    $row['qty_in'],
                    $row['qty_out']
                ]
            );
        }

        // PROFIT EXPORT LOGIC
        if ($type === 'profit') {
            $data = $this->service->getProfitLossReport($start, $end, $productId);
            if ($format === 'pdf') {
                return ExportHelper::toPdf('exports.reports.profit_pdf', ['data' => $data, 'title' => 'Laporan Laba Rugi', 'period' => $period], "Profit_$start.pdf");
            }

            return ExportHelper::toCsv(
                "Profit_$start.csv",
                ['Tanggal', 'Omset', 'HPP', 'Laba Kotor'],
                $data,
                fn($row) => [$row['date'], $row['omzet'], $row['hpp'], $row['gross_profit']]
            );
        }
    }


}
