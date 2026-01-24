<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreRestockRequest;
use App\Http\Requests\Inventory\StoreSoundingRequest;
use App\Services\InventoryService;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {}

    public function storeRestock(StoreRestockRequest $request)
    {
        try {
            $this->inventoryService->handleRestock($request->validated());
            return redirect()->back()->with('success', 'Restock BBM berhasil dicatat. Stok bertambah.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal restock: ' . $e->getMessage());
        }
    }

    public function storeSounding(StoreSoundingRequest $request)
    {
        try {
            $this->inventoryService->handleSounding($request->validated());
            return redirect()->back()->with('success', 'Data sounding tangki berhasil disimpan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal simpan sounding: ' . $e->getMessage());
        }
    }
}
