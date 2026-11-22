<?php

namespace App\Http\Requests;

use App\Models\Garage;
use App\Services\RentalService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuoteRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canRent() ?? false;
    }

    public function rules(): array
    {
        $electric = $this->input('vehicle_type') === 'mobil_listrik';
        $bicycle = $this->input('vehicle_type') === 'sepeda';
        $charging = $electric && Garage::with('garageType')->find($this->integer('garage_id'))?->garageType->prefix === 'E';

        return [
            'vehicle_type' => ['required', Rule::in(['sepeda', 'sepeda_motor', 'mobil', 'mobil_listrik'])],
            'plate_number' => [Rule::excludeIf($bicycle), 'required', 'string', 'max:20'],
            'brand' => ['required', 'string', 'max:100'],
            'model' => ['required', 'string', 'max:100'],
            'battery_capacity_kwh' => [Rule::excludeIf(! $electric), 'required', 'numeric', 'decimal:0,3', 'min:0.001', 'max:9999999.999'],
            'initial_charge_percent' => [Rule::excludeIf(! $charging), 'required', 'numeric', 'decimal:0,2', 'between:0,100'],
            ...RentalService::dateRules(),
            'garage_type_id' => ['required', 'integer', 'exists:garage_types,garage_type_id'],
            'garage_id' => ['required', 'integer', 'exists:garages,garage_id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'battery_capacity_kwh' => 'maximum battery capacity',
            'initial_charge_percent' => 'current charge',
            'garage_type_id' => 'garage type',
            'garage_id' => 'garage',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('tenant.rentals.create');
    }
}
