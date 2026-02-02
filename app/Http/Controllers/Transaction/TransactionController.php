<?php

namespace App\Http\Controllers\Transaction;

use App\Enums\ShipTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Http\Requests\Transaction\UpdateTransactionRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Transaction;
use App\Services\ShiftService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService,
        protected ShiftService $shiftService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        redirect(route('dashboard'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::where('is_active', true)
            ->select('id', 'name', 'price', 'unit', 'stock')
            ->get();

        $customers = Customer::orderBy('manager_name')->get();

        $activeShifts = $this->shiftService->getActiveShiftsMap();

        return Inertia::render('Transaction/Create', [
            'products' => $products,
            'customers' => $customers,
            'shipTypes' => ShipTypeEnum::toArray(),
            'activeShifts' => $activeShifts,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransactionRequest $request)
    {
        try {
            $this->transactionService->createTransaction(
                $request->validated(),
                $request->file('payment_proof')
            );
            return redirect()->route('transactions.create')
                ->with('success', 'Transaksi berhasil disimpan!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Biarkan Laravel menangani error validasi (kembali ke form dengan error merah)
            throw $e;
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal menyimpan transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Transaction $transaction)
    {
        redirect(route('dashboard'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Transaction $transaction)
    {
        redirect(route('dashboard'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTransactionRequest $request, string $id)
    {
        try {
            $transaction = Transaction::findOrFail($id);
            $this->transactionService->updateTransaction($transaction, $request->validated());

            return redirect()->back()->with('success', 'Transaksi berhasil direvisi.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal update: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Transaction $transaction)
    {
        redirect(route('dashboard'));
    }

    /**
     * Return / Void Transaksi
     */
    public function return(Request $request, Transaction $transaction)
    {
        $request->validate(['reason' => 'required|string|max:255']);

        try {
            $this->transactionService->returnTransaction($transaction, $request->reason);

            return redirect()->back()->with('success', 'Transaksi berhasil di-return (Stok dikembalikan).');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal return: ' . $e->getMessage());
        }
    }

    /**
     * Cetak Struk (PDF)
     */
    public function print(Transaction $transaction)
    {
        $pdf = $this->transactionService->generateReceiptPdf($transaction);
        return $pdf->stream("Struk_{$transaction->trx_code}.pdf");
    }
}
