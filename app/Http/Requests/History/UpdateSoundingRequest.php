<?php

namespace App\Http\Requests\History;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateSoundingRequest extends FormRequest
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
            'recorded_at' => ['required', 'date'],
            'physical_height_cm' => ['nullable', 'numeric', 'min:0'],
            'physical_liter' => ['required', 'numeric', 'min:0'],
        ];
    }
}
