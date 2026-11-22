<?php

namespace App\Http\Requests;

use App\Models\GarageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGarageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['daily_rate', 'electricity_rate'] as $field) {
            if ($this->has($field)) {
                $this->merge([$field => $this->normalizeCurrency($this->input($field))]);
            }
        }
    }

    private function normalizeCurrency(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $value = preg_replace('/^Rp\s*/i', '', trim($value));

        if (preg_match('/^[1-9][0-9]{0,2}(?:\.[0-9]{3})+(?:,[0-9]{1,2})?$/D', $value)) {
            return str_replace(',', '.', str_replace('.', '', $value));
        }

        if (preg_match('/^[1-9][0-9]{0,2}(?:,[0-9]{3})+(?:\.[0-9]{1,2})?$/D', $value)) {
            return str_replace(',', '', $value);
        }

        if (preg_match('/^-?[0-9]+(?:,[0-9]{1,2})?$/D', $value)) {
            return str_replace(',', '.', $value);
        }

        return $value;
    }

    public function rules(): array
    {
        $isElectric = GarageType::whereKey($this->integer('garage_type_id'))->value('prefix') === 'E';

        return [
            'garage_type_id' => ['bail', 'required', 'integer', 'exists:garage_types,garage_type_id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'daily_rate' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999999.99'],
            'electricity_rate' => [
                Rule::excludeIf(! $isElectric),
                Rule::requiredIf($isElectric),
                'numeric',
                'decimal:0,2',
                'min:0',
                'max:999999999999.99',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'garage_type_id' => 'Garage type',
            'quantity' => 'Quantity',
            'daily_rate' => 'Daily rate',
            'electricity_rate' => 'Electricity price per kWh',
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute is required.',
            'integer' => ':attribute must be a whole number.',
            'numeric' => ':attribute must be a number.',
            'decimal' => ':attribute must have at most two decimal places.',
            'garage_type_id.exists' => 'The garage type is unavailable.',
            'quantity.min' => 'Quantity must be at least 1.',
            'quantity.max' => 'Quantity must not exceed 999.',
            'daily_rate.min' => 'The daily rate must be greater than 0.',
            'daily_rate.max' => 'The daily rate is too large.',
            'electricity_rate.min' => 'The electricity price must not be negative.',
            'electricity_rate.max' => 'The electricity price is too large.',
        ];
    }
}
