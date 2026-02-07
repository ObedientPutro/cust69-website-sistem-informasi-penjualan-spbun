<?php

namespace App\Http\Controllers\Transaction;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Debt\RepayDebtRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\DebtService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DebtController extends Controller
{
    public function __construct(
        protected DebtService $debtService
    ) {}

    public function index(Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $search = $request->input('search');
        $productId = $request->input('product_id');
        $paymentStatus = $request->input('payment_status');

        $query = Transaction::with(['customer', 'user', 'items.product'])
            ->where('payment_method', PaymentMethodEnum::BON);

        // --- FILTERING ---
        if ($startDate && $endDate) {
            $query->whereBetween('transaction_date', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ]);
        }

        if ($paymentStatus) {
            $query->where('payment_status', $paymentStatus);
        }

        if ($productId) {
            $query->whereHas('items', function($q) use ($productId) {
                $q->where('product_id', $productId);
            });
        }

        if ($search) {
            $query->where(function($subQuery) use ($search) {
                $subQuery->where('trx_code', 'like', "%{$search}%")
                    ->orWhereHas('customer', function($q) use ($search) {
                        $q->where('ship_name', 'like', "%{$search}%");
                    });
            });
        }

        // --- SORTING ---
        // Prioritas: UNPAID (Belum Lunas) ditaruh paling atas, lalu urutkan berdasarkan tanggal terbaru
        $query->orderByRaw("FIELD(payment_status, 'unpaid', 'paid', 'returned')")
            ->latest('transaction_date');

        // --- SUMMARY ---
        // Hitung total piutang yang BELUM LUNAS (sisa hutang aktif)
        $totalUnpaid = (clone $query)->where('payment_status', PaymentStatusEnum::UNPAID)->sum('grand_total');

        $totalBonFilter = (clone $query)->sum('grand_total');

        return Inertia::render('Debt/Index', [
            'debts' => $query->paginate(15)->withQueryString(),
            'products' => Product::select('id', 'name')->orderBy('name')->get(),
            'totalUnpaid' => $totalUnpaid,
            'totalBonFilter' => $totalBonFilter,
            'filters' => [
                'search' => $search,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'product_id' => $productId,
                'payment_status' => $paymentStatus
            ]
        ]);
    }

    public function repay(RepayDebtRequest $request, Transaction $transaction)
    {
        try {
            $this->debtService->processRepayment(
                $transaction,
                $request->validated(),
                $request->file('payment_proof')
            );

            return redirect()->back()->with('success', 'Pelunasan berhasil dicatat. Status Lunas.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses pelunasan: ' . $e->getMessage());
        }
    }
}
