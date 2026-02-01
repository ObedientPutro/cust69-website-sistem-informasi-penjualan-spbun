<?php

namespace App\Services;

use App\Models\Customer;
use App\Traits\NotificationHelper;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        $allowedSorts = ['manager_name', 'owner_name', 'ship_name', 'phone', 'credit_limit', 'gross_tonnage', 'is_active', 'created_at'];

        if (!in_array($sortColumn, $allowedSorts)) {
            $sortColumn = 'created_at';
        }

        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        return Customer::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('manager_name', 'like', "%{$search}%")
                        ->orWhere('owner_name', 'like', "%{$search}%")
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
            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                $data['photo'] = $data['photo']->store('customers/photos', 'public');
            }

            $customer = Customer::create($data);

            NotificationHelper::send(
                'Pelanggan Baru',
                "Operator " . Auth::user()->name . " mendaftarkan pelanggan baru: {$data['manager_name']} (Kapal: {$data['ship_name']}).",
                route('customers.index'),
                'info'
            );

            return $customer;
        });
    }

    /**
     * Update Customer dengan Transaction
     */
    public function updateCustomer(Customer $customer, array $data): Customer
    {
        return DB::transaction(function () use ($customer, $data) {
            if (isset($data['photo']) && $data['photo'] instanceof UploadedFile) {
                if ($customer->photo) {
                    Storage::disk('public')->delete($customer->photo);
                }
                $data['photo'] = $data['photo']->store('customers/photos', 'public');
            } else {
                unset($data['photo']);
            }

            $customer->update($data);

            NotificationHelper::send(
                'Data Pelanggan Diubah',
                "Data pelanggan {$customer->manager_name} diperbarui oleh " . Auth::user()->name,
                route('customers.index'),
                'info'
            );

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
                if ($customer->photo) {
                    Storage::disk('public')->delete($customer->photo);
                }
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
