<?php

namespace App\Http\Controllers\Core;

use App\Enums\ShipTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Requests\Customer\UpdateLimitRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function __construct(
        protected CustomerService $customerService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $customers = $this->customerService->getCustomers(
            search: $request->input('search'),
            perPage: 10,
            sortColumn: $request->input('sort', 'created_at'),
            sortDirection: $request->input('direction', 'desc')
        );

        return Inertia::render('Customer/Index', [
            'customers' => $customers,
            'filters'   => $request->only(['search', 'sort', 'direction']),
            'shipTypes' => ShipTypeEnum::toArray(),
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
    public function store(StoreCustomerRequest $request)
    {
        try {
            $this->customerService->createCustomer($request->validated());
            return redirect()->back()->with('success', 'Data Nelayan berhasil disimpan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan data nelayan: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        redirect(route('dashboard'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        redirect(route('dashboard'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        try {
            $this->customerService->updateCustomer($customer, $request->validated());
            return redirect()->back()->with('success', 'Data Nelayan diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        try {
            $this->customerService->deleteCustomer($customer);
            return redirect()->back()->with('success', 'Data Nelayan berhasil dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function toggleStatus(Customer $customer)
    {
        try {
            $status = $this->customerService->toggleStatus($customer);
            return redirect()->back()->with('success', "Pelanggan berhasil {$status}.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal update status: ' . $e->getMessage());
        }
    }

    /**
     * Handle Update Limit (Terpisah)
     */
    public function updateLimit(UpdateLimitRequest $request, Customer $customer)
    {
        try {
            $this->customerService->updateCreditLimit($customer, $request->validated());
            return redirect()->back()->with('success', 'Limit Kredit berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal update limit: ' . $e->getMessage());
        }
    }
}
