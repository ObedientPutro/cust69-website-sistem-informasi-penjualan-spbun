<?php

namespace App\Http\Requests\Customer;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', Rule::unique(Customer::class, 'phone')],
            'ship_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'photo' => ['nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png'],
        ];
    }
}
