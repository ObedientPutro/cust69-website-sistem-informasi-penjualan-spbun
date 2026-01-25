<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Transaction\StoreTransactionRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function __construct(
        protected TransactionService $transactionService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $products = Product::where('is_active', true)
            ->select('id', 'name', 'price', 'unit', 'stock')
            ->get();

        $customers = Customer::orderBy('name')->get();

        return Inertia::render('Transaction/Create', [
            'products' => $products,
            'customers' => $customers,
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
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
