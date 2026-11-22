<?php

namespace App\Services;

use App\Models\Garage;
use App\Models\GarageType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GarageService
{
    public function syncAvailability(string $date): void
    {
        $active = fn ($query) => $query->activeOn($date);

        Garage::whereHas('rentals', $active)->where('status', '!=', 'used')->update(['status' => 'used']);
        Garage::whereDoesntHave('rentals', $active)->where('status', '!=', 'available')->update(['status' => 'available']);
    }

    public function createBatch(
        int $typeId,
        int $quantity,
        string $dailyRate,
        ?string $electricityRate = null,
    ): Collection {
        Validator::make(
            [
                'quantity' => $quantity,
                'daily_rate' => $dailyRate,
                'electricity_rate' => $electricityRate,
            ],
            [
                'quantity' => ['required', 'integer', 'min:1', 'max:999'],
                'daily_rate' => [
                    'required', 'numeric', 'decimal:0,2',
                    'min:0.01', 'max:999999999999.99',
                ],
                'electricity_rate' => [
                    'nullable', 'numeric', 'decimal:0,2',
                    'min:0', 'max:999999999999.99',
                ],
            ],
        )->validate();

        return DB::transaction(function () use (
            $typeId,
            $quantity,
            $dailyRate,
            $electricityRate,
        ) {
            $type = GarageType::whereKey($typeId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($type->last_number + $quantity > 999) {
                throw ValidationException::withMessages([
                    'quantity' => 'Garage numbering for this type cannot exceed 999.',
                ]);
            }

            if ($type->prefix === 'E' && $electricityRate === null) {
                throw ValidationException::withMessages([
                    'electricity_rate' => 'An electricity price is required for electric garages.',
                ]);
            }

            $garages = collect();

            for ($i = 1; $i <= $quantity; $i++) {
                $number = $type->last_number + $i;
                $code = sprintf('%s%03d', $type->prefix, $number);

                $garages->push(Garage::create([
                    'garage_type_id' => $type->garage_type_id,
                    'code' => $code,
                    'daily_rate' => $dailyRate,
                    'electricity_rate' => $type->prefix === 'E'
                        ? $electricityRate
                        : null,
                ]));
            }

            $type->last_number += $quantity;
            $type->save();

            return $garages;
        });
    }
}
