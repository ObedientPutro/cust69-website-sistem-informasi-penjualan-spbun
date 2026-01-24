<?php

namespace App\Http\Controllers\Transaction;

use App\Enums\PaymentStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $query = Transaction::with(['customer', 'user', 'items.product'])
            ->where('payment_status', PaymentStatusEnum::UNPAID); // Filter Status: UNPAID

        if ($search) {
            $query->whereHas('customer', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        // Summary Total Piutang
        $totalDebt = Transaction::where('payment_status', PaymentStatusEnum::UNPAID)->sum('grand_total');

        return Inertia::render('Debt/Index', [
            'debts' => $query->latest('transaction_date')->paginate(10)->withQueryString(),
            'totalDebt' => $totalDebt,
            'filters' => ['search' => $search]
        ]);
    }

    public function repay(Request $request, Transaction $transaction)
    {
        $request->validate([
            'repayment_method' => ['required', 'in:cash,transfer'],
            'payment_proof' => ['nullable', 'image', 'max:2048'],
        ]);

        DB::transaction(function() use ($request, $transaction) {
            $updateData = [
                'payment_status' => PaymentStatusEnum::PAID,
                'repayment_method' => $request->repayment_method,
                'paid_at' => Carbon::now(),
            ];

            // Jika Transfer dan ada bukti
            if ($request->hasFile('payment_proof')) {
                $updateData['payment_proof'] = $request->file('payment_proof')->store('repayment_proofs', 'public');
            }

            $transaction->update($updateData);
        });

        return redirect()->back()->with('success', 'Pelunasan berhasil dicatat. Status Lunas.');
    }
}
