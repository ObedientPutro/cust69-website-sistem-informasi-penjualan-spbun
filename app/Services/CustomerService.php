<?php

namespace App\Services;

use App\Models\Customer;

class CustomerService
{
    public function createCustomer(array $data): Customer
    {
        return Customer::create($data);
    }

    public function updateCustomer(Customer $customer, array $data): Customer
    {
        $customer->update($data);
        return $customer;
    }

    public function deleteCustomer(Customer $customer): void
    {
        // Cek apakah punya transaksi? Jika ya, soft delete / disable saja
        // Disini kita asumsi disable jika ada relasi (handled by controller or frontend logic)
        $customer->delete();
    }
}
