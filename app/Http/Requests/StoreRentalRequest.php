<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canRent() ?? false;
    }

    public function rules(): array
    {
        return [
            'booking_quote' => ['required', 'string', 'max:12000'],
            'payment_receipt' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('tenant.rentals.create');
    }
}
