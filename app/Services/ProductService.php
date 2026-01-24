<?php

namespace App\Services;

use App\Enums\ProductPriceHistoryTypeEnum;
use App\Models\Product;
use App\Models\ProductPriceHistory;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductService
{
    /**
     * Get Products for DataTable
     */
    public function getProducts(
        string $search = null,
        int $perPage = 10,
        ?string $sortColumn = 'created_at',
        ?string $sortDirection = 'desc'
    ): LengthAwarePaginator
    {
        $allowedSorts = ['name', 'price', 'cost_price', 'stock', 'unit', 'created_at'];
        $sortColumn = in_array($sortColumn, $allowedSorts) ? $sortColumn : 'created_at';
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return Product::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create Product
     */
    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            return Product::create([
                'name'       => $data['name'],
                'unit'       => $data['unit'] ?? 'Liter',
                'price'      => $data['price'] ?? 0,
                'cost_price' => $data['cost_price'] ?? 0,
                'stock'      => $data['stock'] ?? 0,
            ]);
        });
    }

    /**
     * Update Product
     * Note: Stok biasanya tidak diupdate disini kecuali koreksi data master.
     */
    public function updateProduct(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $oldPrice = $product->price;
            $oldCostPrice = $product->cost_price;

            $updateData = [
                'name' => $data['name'],
                'unit' => $data['unit'],
                'price' => $data['price'],
                'cost_price' => $data['cost_price'] ?? $product->cost_price,
            ];

            if (isset($data['stock'])) {
                $updateData['stock'] = $data['stock'];
            }

            $product->update($updateData);

            // CEK PERUBAHAN HARGA (Logic Tracking)
            // Jika Harga Jual BERUBAH atau HPP BERUBAH, catat history
            if ($oldPrice != $product->price || $oldCostPrice != $product->cost_price) {
                ProductPriceHistory::create([
                    'product_id' => $product->id,
                    'user_id' => Auth::id(),
                    'old_price' => $oldPrice,
                    'new_price' => $product->price,
                    'old_cost_price' => $oldCostPrice,
                    'new_cost_price' => $product->cost_price,
                    'type' => ProductPriceHistoryTypeEnum::MANUAL_UPDATE,
                ]);
            }

            return $product;
        });
    }

    /**
     * Delete Product (Safe Delete)
     */
    public function deleteProduct(Product $product): void
    {
        try {
            DB::transaction(function () use ($product) {
                $product->delete();
            });
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() === '23000') {
                throw new \Exception('Produk tidak dapat dihapus karena masih memiliki stok atau riwayat transaksi.');
            }
            throw $e;
        }
    }

    /**
     * Toggle Status Active/Inactive
     */
    public function toggleStatus(Product $product): Product
    {
        $product->update([
            'is_active' => !$product->is_active
        ]);
        return $product;
    }
}
