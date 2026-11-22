<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'verification_status' => ['required', Rule::in(['approved', 'rejected'])],
        ];
    }

    public function messages(): array
    {
        return [
            'verification_status.required' => 'Choose approve or reject.',
            'verification_status.in' => 'Choose approve or reject.',
        ];
    }
}
