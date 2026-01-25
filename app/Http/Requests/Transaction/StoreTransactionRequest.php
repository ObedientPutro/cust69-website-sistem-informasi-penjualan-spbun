<?php

namespace App\Http\Requests\Transaction;

use App\Enums\PaymentMethodEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreTransactionRequest extends FormRequest
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
            'transaction_date' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity_liter' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::enum(PaymentMethodEnum::class)],

            'customer_id' => [
                'nullable',
                'exists:customers,id',
                // Jika Bon, Customer Wajib Diisi
                Rule::requiredIf(fn() => $this->payment_method === PaymentMethodEnum::BON->value)
            ],

            'payment_proof' => [
                'nullable',
                'image', 'max:5120',
                // Jika Transfer, Bukti Wajib Diupload
                Rule::requiredIf(fn() => $this->payment_method === PaymentMethodEnum::TRANSFER->value)
            ],

            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required_if' => 'Nama Pelanggan wajib dipilih untuk pembayaran Bon/Piutang.',
            'payment_proof.required_if' => 'Bukti Transfer wajib diunggah untuk pembayaran via Transfer.',
        ];
    }
}
