<?php

namespace App\Http\Requests\Customer;

use App\Enums\ShipTypeEnum;
use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('access-dashboard');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'manager_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'ship_name' => ['required', 'string', 'max:255'],
            'ship_type' => ['required', Rule::enum(ShipTypeEnum::class)],
            'gross_tonnage' => ['required', 'numeric', 'min:0'],
            'pk_engine' => ['required', 'numeric', 'min:0'],
            'phone' => ['required', 'string', 'max:20', Rule::unique(Customer::class, 'phone')->ignore($this->customer)],
            'address' => ['required', 'string', 'max:500'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'photo' => ['nullable', 'image', 'max:5120', 'mimes:jpg,jpeg,png'],
        ];
    }
}
