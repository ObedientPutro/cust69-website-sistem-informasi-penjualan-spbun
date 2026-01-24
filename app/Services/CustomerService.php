<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    /**
     * Mengambil data customer dengan Search, Pagination, dan Sorting.
     */
    public function getCustomers(
        string $search = null,
        int $perPage = 10,
        string $sortColumn = 'created_at',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator
    {
        $allowedSorts = ['name', 'ship_name', 'phone', 'credit_limit', 'is_active', 'created_at'];

        if (!in_array($sortColumn, $allowedSorts)) {
            $sortColumn = 'created_at';
        }

        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return Customer::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('ship_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy($sortColumn, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create Customer dengan Transaction
     */
    public function createCustomer(array $data): Customer
    {
        return DB::transaction(function () use ($data) {
            return Customer::create($data);
        });
    }

    /**
     * Update Customer dengan Transaction
     */
    public function updateCustomer(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            $customer->update($data);
            return $customer;
        });
    }

    /**
     * Delete Customer dengan Pengecekan Relasi (Foreign Key)
     */
    public function deleteCustomer(Customer $customer): void
    {
        try {
            DB::transaction(function () use ($customer) {
                $customer->delete();
            });
        } catch (\Illuminate\Database\QueryException $e) {
            // Error Code 23000 = Integrity constraint violation
            if ($e->getCode() === '23000') {
                throw new \Exception('Data Nelayan tidak dapat dihapus karena memiliki riwayat transaksi / bon.');
            }
            throw $e;
        }
    }

    /**
     * Toggle Status Active/Inactive
     */
    public function toggleStatus(Customer $customer): string
    {
        $customer->update(['is_active' => !$customer->is_active]);
        return $customer->is_active ? 'diaktifkan' : 'dinonaktifkan';
    }
}
