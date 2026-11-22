<?php

namespace App\Services;

use App\Models\Garage;
use App\Models\Rental;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class RentalService
{
    public function __construct(
        private PricingService $pricing,
        private PaymentService $payments,
    ) {}

    public static function dateRules(): array
    {
        return [
            'booking_type' => ['required', Rule::in(['daily', 'monthly'])],
            'start_date' => ['bail', 'required', 'date_format:Y-m-d', 'after_or_equal:today', 'before:9999-12-31'],
            'end_date' => ['bail', 'required', 'date_format:Y-m-d', 'after:start_date', 'before_or_equal:9999-12-31'],
        ];
    }

    public function availableGarages(string $vehicleType, int $typeId, string $bookingType, string $startDate, string $endDate): Collection
    {
        [$start, $end] = $this->rentalDates($bookingType, $startDate, $endDate);
        $prefixes = match ($vehicleType) {
            'sepeda', 'sepeda_motor' => ['S'],
            'mobil' => ['C'],
            'mobil_listrik' => ['C', 'E'],
            default => [],
        };

        return Garage::with('garageType')
            ->where('garage_type_id', $typeId)
            ->whereHas('garageType', fn ($query) => $query->whereIn('prefix', $prefixes))
            ->whereDoesntHave('rentals', fn ($query) => $query
                ->whereDate('start_date', '<', $end->toDateString())
                ->whereDate('end_date', '>', $start->toDateString()))
            ->orderBy('code')->get();
    }

    public function quote(User $tenant, array $data): array
    {
        abort_unless($tenant->canRent(), 403);
        $garage = Garage::with('garageType')->findOrFail($data['garage_id']);

        return $this->quoteForGarage($tenant, $garage, $data);
    }

    private function quoteForGarage(User $tenant, Garage $garage, array $data): array
    {
        if ($garage->garage_type_id !== (int) $data['garage_type_id'] || ! $garage->isCompatible($data['vehicle_type'])) {
            throw ValidationException::withMessages(['garage_id' => 'Select a garage compatible with your vehicle.']);
        }

        [$start, $end] = $this->rentalDates($data['booking_type'], $data['start_date'], $data['end_date']);
        $days = (int) $start->diffInDays($end);

        if ($garage->rentals()->whereDate('start_date', '<', $end->toDateString())
            ->whereDate('end_date', '>', $start->toDateString())->exists()) {
            throw ValidationException::withMessages(['garage_id' => 'This garage is no longer available for these dates.']);
        }

        $rentalAmount = $this->pricing->rentalCharge($data['booking_type'], $days, $garage->daily_rate);
        $charging = $garage->garageType->prefix === 'E';
        $kwh = $charging
            ? $this->pricing->chargingKwh((string) $data['battery_capacity_kwh'], (string) $data['initial_charge_percent'])
            : '0.000';
        $electricityAmount = $charging ? $this->pricing->electricityCharge($kwh, $garage->electricity_rate) : '0.00';

        return [
            ...$data,
            'tenant_id' => $tenant->user_id,
            'garage_id' => $garage->garage_id,
            'garage_code' => $garage->code,
            'garage_type' => $garage->garageType->displayName(),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'duration_days' => $days,
            'base_month_days' => $start->daysInMonth,
            'booked_daily_rate' => $garage->daily_rate,
            'booked_electricity_rate' => $charging ? $garage->electricity_rate : null,
            'electricity_kwh' => $kwh,
            'rental_amount' => $rentalAmount,
            'electricity_amount' => $electricityAmount,
            'total_amount' => $this->pricing->addAmounts($rentalAmount, $electricityAmount, 'garage_id'),
        ];
    }

    public function createForTenant(User $tenant, array $quote, UploadedFile $proof): Rental
    {
        abort_unless($tenant->canRent() && $quote['tenant_id'] === $tenant->user_id, 403);
        $payment = null;

        try {
            return DB::transaction(function () use ($tenant, $quote, $proof, &$payment) {
                $currentTenant = User::whereKey($tenant->user_id)->lockForUpdate()->firstOrFail();
                abort_unless($currentTenant->canRent(), 403);
                $garage = Garage::with('garageType')->whereKey($quote['garage_id'])->lockForUpdate()->firstOrFail();
                if ($quote['start_date'] < today()->toDateString()) {
                    throw ValidationException::withMessages([
                        'booking_quote' => 'The start date has passed. Calculate your booking again.',
                    ]);
                }
                $current = $this->quoteForGarage($currentTenant, $garage, $quote);

                foreach (['start_date', 'end_date', 'duration_days', 'booked_daily_rate', 'booked_electricity_rate', 'total_amount'] as $field) {
                    if ($quote[$field] !== $current[$field]) {
                        throw ValidationException::withMessages([
                            'booking_quote' => 'The date or price has changed. Calculate your booking again.',
                        ]);
                    }
                }

                $vehicle = new Vehicle([
                    'type' => $quote['vehicle_type'],
                    'plate_number' => $quote['vehicle_type'] === 'sepeda' ? null : $quote['plate_number'],
                    'brand' => $quote['brand'],
                    'model' => $quote['model'],
                    'battery_capacity_kwh' => $quote['vehicle_type'] === 'mobil_listrik' ? $quote['battery_capacity_kwh'] : null,
                ]);
                $vehicle->user()->associate($currentTenant);
                $vehicle->save();
                $rental = Rental::create([
                    'tenant_id' => $currentTenant->user_id,
                    'created_by' => $currentTenant->user_id,
                    'vehicle_id' => $vehicle->vehicle_id,
                    'garage_id' => $garage->garage_id,
                    'booking_type' => $quote['booking_type'],
                    'start_date' => $current['start_date'],
                    'end_date' => $current['end_date'],
                    'base_month_days' => $current['base_month_days'],
                    'booked_daily_rate' => $current['booked_daily_rate'],
                    'booked_electricity_rate' => $current['booked_electricity_rate'],
                    'booked_battery_capacity_kwh' => $quote['vehicle_type'] === 'mobil_listrik' ? $quote['battery_capacity_kwh'] : null,
                    'initial_charge_percent' => $current['booked_electricity_rate'] !== null ? $quote['initial_charge_percent'] : null,
                    'electricity_kwh' => $current['electricity_kwh'],
                    'rental_amount' => $current['rental_amount'],
                    'electricity_amount' => $current['electricity_amount'],
                ]);
                $garage->forceFill([
                    'status' => $garage->rentals()->activeOn(today()->toDateString())->exists() ? 'used' : 'available',
                ])->save();
                $payment = $this->payments->recordPayment($rental, $currentTenant, $current['total_amount'], $proof);

                return $rental;
            });
        } catch (Throwable $exception) {
            if ($payment !== null) {
                Storage::disk('local')->delete($payment->proof_path);
            }

            throw $exception;
        }
    }

    public function rentalDates(string $type, string $startDate, string $endDate): array
    {
        Validator::make([
            'booking_type' => $type, 'start_date' => $startDate, 'end_date' => $endDate,
        ], self::dateRules())->validate();
        $start = CarbonImmutable::createFromFormat('!Y-m-d', $startDate);
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $endDate);

        $maximumDays = $start->daysInMonth - 1;

        if ($type === 'daily' && $start->diffInDays($end) > $maximumDays) {
            throw ValidationException::withMessages(['end_date' => 'Daily bookings starting in this month cannot exceed '.$maximumDays.' days.']);
        }

        if ($type === 'monthly' && ! $end->isSameDay($start->addMonthNoOverflow())) {
            throw ValidationException::withMessages(['end_date' => 'Monthly bookings must cover exactly one calendar month.']);
        }

        return [$start, $end];
    }

    public function extensionQuote(Rental $rental, string $endDate): array
    {
        $end = CarbonImmutable::createFromFormat('!Y-m-d', $endDate);
        $quote = $this->pricing->extensionBreakdown($rental->end_date, $end, $rental->booked_daily_rate);
        $quote['rental_amount'] = $this->pricing->addAmounts($rental->rental_amount, $quote['amount']);

        if (Rental::where('garage_id', $rental->garage_id)
            ->where('rental_id', '!=', $rental->rental_id)
            ->whereDate('start_date', '<', $endDate)
            ->whereDate('end_date', '>', $rental->end_date->toDateString())
            ->exists()) {
            throw ValidationException::withMessages([
                'end_date' => 'The garage is already booked during the extension.',
            ]);
        }

        return $quote;
    }

    public function extendRental(
        User $admin,
        Rental $rental,
        string $newEnd,
        string $expectedEnd,
        UploadedFile $proof,
    ): Rental {
        abort_unless($admin->isAdmin(), 403);
        $payment = null;

        try {
            return DB::transaction(function () use ($admin, $rental, $newEnd, $expectedEnd, $proof, &$payment) {
                Garage::whereKey($rental->garage_id)->lockForUpdate()->firstOrFail();
                $current = Rental::whereKey($rental->rental_id)->lockForUpdate()->firstOrFail();

                if (! $current->isActiveOn(today())) {
                    throw ValidationException::withMessages([
                        'end_date' => 'Only current bookings can be extended.',
                    ]);
                }

                if ($current->end_date->toDateString() !== $expectedEnd) {
                    throw ValidationException::withMessages([
                        'end_date' => 'This booking has changed. Calculate the fee again before extending it.',
                    ]);
                }

                $quote = $this->extensionQuote($current, $newEnd);

                $current->update([
                    'end_date' => $newEnd,
                    'rental_amount' => $quote['rental_amount'],
                ]);
                $payment = $this->payments->recordPayment($current, $admin, $quote['amount'], $proof);

                return $current;
            });
        } catch (Throwable $exception) {
            if ($payment !== null) {
                Storage::disk('local')->delete($payment->proof_path);
            }

            throw $exception;
        }
    }
}
