<?php

namespace App\Http\Controllers\Transaction;

use App\Enums\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DebtController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    public function index(Request $request)
    {
        $search = $request->search;

        $query = Transaction::with(['customer', 'user', 'items.product'])
            ->where('payment_status', PaymentStatusEnum::UNPAID);

        if ($search) {
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $totalDebt = Transaction::where('payment_status', PaymentStatusEnum::UNPAID)->sum('grand_total');

        return Inertia::render('Debt/Index', [
            'debts' => $query->latest('transaction_date')->paginate(10)->withQueryString(),
            'totalDebt' => $totalDebt,
            'filters' => ['search' => $search]
        ]);
    }

    public function repay(UpdateTransactionRequest $request, Transaction $transaction)
    {
        try {
            $this->transactionService->processRepayment(
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
