<?php

namespace App\Http\Requests\Shift;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class CloseShiftRequest extends FormRequest
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
            'closing_totalizer' => ['required', 'numeric', 'min:0'],
            'cash_collected' => ['required', 'numeric', 'min:0'],
            'closing_proof' => ['nullable', 'image', 'max:5120'],
        ];
    }
}
