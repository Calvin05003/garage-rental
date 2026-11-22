<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExtendRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $saving = $this->isMethod('post');
        $currentEnd = $this->route('rental')->end_date->toDateString();

        return [
            'end_date' => [
                'bail', $saving ? 'required' : 'sometimes', 'date_format:Y-m-d', 'after:'.$currentEnd,
            ],
            'expected_end_date' => [$saving ? 'required' : 'exclude', 'date_format:Y-m-d'],
            'payment_receipt' => [
                $saving ? 'required' : 'exclude', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:5120',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'end_date' => 'new end date',
            'payment_receipt' => 'payment receipt',
        ];
    }

    protected function getRedirectUrl(): string
    {
        $parameters = ['rental' => $this->route('rental')];

        if ($this->isMethod('post') && ! $this->validator->errors()->has('end_date')) {
            $parameters['end_date'] = $this->input('end_date');
        }

        return route('admin.rentals.edit', $parameters);
    }
}
