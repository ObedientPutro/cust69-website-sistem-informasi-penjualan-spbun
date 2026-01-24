<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
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
        $query = Customer::query();
        if ($request->search) {
            $query->where('name', 'like', "%{$request->search}%")
                ->orWhere('ship_name', 'like', "%{$request->search}%");
        }

        return Inertia::render('Customer/Index', [
            'customers' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => ['search' => $request->search]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
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
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        try {
            $this->customerService->updateCustomer($customer, $request->validated());
            return redirect()->back()->with('success', 'Data Nelayan diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui data nelayan: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        try {
            $this->customerService->deleteCustomer($customer);
            return redirect()->back()->with('success', 'Data dihapus.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal hapus: Data sedang digunakan.');
        }
    }
}
