<?php

namespace App\Http\Requests\Setting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateSiteSettingRequest extends FormRequest
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
            'site_name' => ['sometimes', 'required', 'string', 'max:255'],
            'address' => ['sometimes', 'required', 'string', 'max:500'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'public_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'notification_email' => ['sometimes', 'required', 'email', 'max:255'],
            'enable_email_notification' => ['sometimes', 'boolean'],
            'enable_web_notification' => ['sometimes', 'boolean'],
            'logo_left' => ['nullable', 'image', 'max:2048'],
            'logo_right' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'notification_email.required' => 'Alamat email tidak boleh kosong jika ingin disimpan.',
            'notification_email.email' => 'Format email tidak valid.',
        ];
    }
}
