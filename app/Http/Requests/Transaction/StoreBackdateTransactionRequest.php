<?php

namespace App\Http\Requests\Transaction;

use App\Enums\PaymentMethodEnum;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreBackdateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('access-owner');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'transaction_date' => ['required', 'date', 'before_or_equal:today'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', Rule::exists(Product::class, 'id')],
            'items.*.quantity_liter' => ['required', 'numeric', 'min:0.01'],

            'payment_method' => ['required', Rule::enum(PaymentMethodEnum::class)],
            'customer_id' => ['required', Rule::exists(Customer::class, 'id')],

            'payment_proof' => ['nullable', 'image', 'max:5120'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_date.before_or_equal' => 'Tanggal transaksi tidak boleh masa depan.',
            'customer_id.required' => 'Pelanggan wajib dipilih untuk pencatatan administratif.',
        ];
    }
}
