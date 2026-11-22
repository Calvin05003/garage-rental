<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }
    }

    public function rules(): array
    {
        return [
            'nik' => ['required', 'string', 'regex:/^[0-9]{16}$/', 'unique:users,nik'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:72', 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nik' => 'NIK',
            'name' => 'Full name',
            'phone' => 'Phone number',
            'email' => 'Email',
            'password' => 'Password',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute is required.',
            'string' => ':attribute must be text.',
            'max' => ':attribute must not exceed :max characters.',
            'nik.regex' => 'NIK must contain exactly 16 digits.',
            'nik.unique' => 'This NIK is already registered.',
            'phone.regex' => 'Enter 8–15 digits, optionally starting with +.',
            'email.email' => 'Enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.min' => 'Password must contain at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }
}
