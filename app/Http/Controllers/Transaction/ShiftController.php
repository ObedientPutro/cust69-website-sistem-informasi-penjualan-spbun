<?php

namespace App\Http\Controllers\Transaction;

use App\Enums\PumpShiftStatusEnum;
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
    public function index(Request $request)
    {
        $products = Product::where('is_active', true)
            ->select('id', 'name', 'stock', 'unit')
            ->orderBy('name')
            ->get();

        $activeShifts = PumpShift::with(['opener:id,name,photo'])
            ->where('status', PumpShiftStatusEnum::OPEN)
            ->get()
            ->keyBy('product_id');

        $query = PumpShift::with(['product', 'opener:id,name', 'closer:id,name']);

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if ($startDate && $endDate) {
            $query->whereBetween('opened_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ]);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->input('product_id'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->whereHas('product', fn($p) => $p->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('opener', fn($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhere('status', 'like', "%{$search}%");
            });
        }

        $sortColumn = $request->input('sort', 'opened_at');
        $sortDirection = $request->input('direction', 'desc');

        $allowedSorts = ['opened_at', 'total_sales_liter', 'status', 'opening_totalizer', 'closing_totalizer'];
        if (in_array($sortColumn, $allowedSorts)) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->latest('opened_at');
        }

        $history = $query->paginate(10)->withQueryString();

        return Inertia::render('Shift/Index', [
            'products' => $products,
            'activeShifts' => $activeShifts,
            'history' => $history,
            'filters' => $request->only(['search', 'sort', 'direction', 'start_date', 'end_date', 'product_id']),
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
