<?php

namespace App\Http\Requests\Transaction;

use App\Enums\PaymentMethodEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateTransactionRequest extends FormRequest
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
            'repayment_method' => ['required', Rule::enum(PaymentMethodEnum::class)],

            'payment_proof' => [
                'nullable',
                'image', 'max:5120',
                Rule::requiredIf(fn() => $this->repayment_method === PaymentMethodEnum::TRANSFER->value)
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_proof.required_if' => 'Bukti transfer wajib diunggah untuk metode Transfer.',
        ];
    }
}
