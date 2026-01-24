<?php

namespace App\Services;

use App\Enums\ProductPriceHistoryTypeEnum;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use App\Models\Restock;
use App\Models\TankSounding;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    /**
     * Handle Restock (Penebusan DO)
     * Menambah Stok & Update Harga Rata-rata (Weighted Average Cost)
     */
    public function handleRestock(array $data): Restock
    {
        return DB::transaction(function () use ($data) {
            $product = Product::where('id', $data['product_id'])->lockForUpdate()->firstOrFail();

            $incomingVolume = $data['volume_liter'];
            $incomingTotalCost = $data['total_cost'];
            $incomingUnitCost = $incomingVolume > 0 ? ($incomingTotalCost / $incomingVolume) : 0;

            $oldCostPrice = $product->cost_price;
            $oldStock = $product->stock;
            $oldPrice = $product->price;

            // Kalkulasi Weighted Average Cost (HPP Baru)
            // Rumus: ((Stok Lama * HPP Lama) + (Stok Baru * HPP Baru)) / Total Stok
            // Note: Jika stok lama minus, kita anggap 0 untuk perhitungan HPP agar tidak merusak harga
            $effectiveOldStock = max(0, $oldStock);
            $currentValuation = $effectiveOldStock * $oldCostPrice;
            $newValuation = $currentValuation + $incomingTotalCost;
            $totalNewVolume = $effectiveOldStock + $incomingVolume;

            $newCostPrice = $totalNewVolume > 0 ? ($newValuation / $totalNewVolume) : $incomingUnitCost;

            // Update Product (Stok bertambah, HPP berubah)
            $product->stock = $oldStock + $incomingVolume;
            $product->cost_price = $newCostPrice;
            $product->save();

            // Catat History Perubahan Harga (HPP)
            if (abs($oldCostPrice - $newCostPrice) > 0.01) {
                ProductPriceHistory::create([
                    'product_id' => $product->id,
                    'user_id' => Auth::id(),
                    'old_price' => $oldPrice,
                    'new_price' => $oldPrice, // Harga jual tidak berubah
                    'old_cost_price' => $oldCostPrice,
                    'new_cost_price' => $newCostPrice,
                    'type' => ProductPriceHistoryTypeEnum::RESTOCK_ADJUSTMENT,
                ]);
            }

            return Restock::create([
                'user_id' => Auth::id(),
                'product_id' => $data['product_id'],
                'date' => $data['date'],
                'volume_liter' => $incomingVolume,
                'total_cost' => $incomingTotalCost,
                'unit_cost' => $incomingUnitCost,
                'note' => $data['note'] ?? null,
            ]);
        });
    }

    /**
     * REQ-B03: Handle Tank Sounding (Stok Opname)
     * Mencatat selisih fisik vs sistem.
     */
    public function handleSounding(array $data): TankSounding
    {
        return DB::transaction(function () use ($data) {
            // Lock product untuk snapshot akurat
            $product = Product::where('id', $data['product_id'])->lockForUpdate()->firstOrFail();

            $systemStockSnapshot = $product->stock;
            $physicalStock = $data['physical_liter'];

            // Hitung Selisih: Fisik - Sistem
            // Positif = Gain (Untung/Lebih), Negatif = Losses (Susut/Bocor)
            $difference = $physicalStock - $systemStockSnapshot;

            // Create Record
            $sounding = TankSounding::create([
                'user_id' => Auth::id(),
                'product_id' => $product->id,
                'recorded_at' => $data['recorded_at'],
                'physical_height_cm' => $data['physical_height_cm'],
                'physical_liter' => $physicalStock,
                'system_liter_snapshot' => $systemStockSnapshot,
                'difference' => $difference,
            ]);

            // OPTIONAL: Auto-adjust system stock to match physical?
            // Biasanya sounding hanya untuk audit, penyesuaian stok dilakukan via "Stock Adjustment".
            // Namun jika ingin sistem = fisik, uncomment baris bawah:
            // $product->update(['stock' => $physicalStock]);

            return $sounding;
        });
    }
}
