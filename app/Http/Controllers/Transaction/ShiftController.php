<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shift\CloseShiftRequest;
use App\Http\Requests\Shift\OpenShiftRequest;
use App\Models\Product;
use App\Models\PumpShift;
use App\Services\ShiftService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShiftController extends Controller
{
    public function __construct(
        protected ShiftService $shiftService
    ){}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Ambil Shift Aktif User Login
        $activeShift = $this->shiftService->getCurrentActiveShift();

        // Ambil History Shift (Pagination)
        $history = PumpShift::with(['product', 'user'])
            ->where('user_id', auth()->id()) // Operator lihat history dia sendiri
            ->latest()
            ->paginate(10);

        // Ambil Produk yang tersedia untuk dibuka (Opsional, buat dropdown)
        $products = Product::where('is_active', true)->get();

        return Inertia::render('Shift/Index', [
            'activeShift' => $activeShift,
            'history' => $history,
            'products' => $products
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        redirect(route('dashboard'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(OpenShiftRequest $request)
    {
        try {
            $this->shiftService->openShift($request->validated());
            return redirect()->back()->with('success', 'Shift berhasil dibuka. Silakan mulai transaksi.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        redirect(route('dashboard'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        redirect(route('dashboard'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(CloseShiftRequest $request, PumpShift $shift)
    {
        try {
            $this->shiftService->closeShift($shift, $request->validated());
            return redirect()->back()->with('success', 'Shift ditutup. Data tersimpan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        redirect(route('dashboard'));
    }
}
