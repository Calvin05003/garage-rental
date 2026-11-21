<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'tenant_id',
    'created_by',
    'previous_rental_id',
    'vehicle_id',
    'garage_id',
    'booking_type',
    'start_date',
    'end_date',
    'base_month_days',
    'booked_daily_rate',
    'booked_electricity_rate',
    'booked_battery_capacity_kwh',
    'initial_charge_percent',
    'electricity_kwh',
    'rental_amount',
    'electricity_amount',
    'status',
])]
class Rental extends Model
{
    protected $primaryKey = 'rental_id';

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'base_month_days' => 'integer',
            'booked_daily_rate' => 'decimal:2',
            'booked_electricity_rate' => 'decimal:2',
            'booked_battery_capacity_kwh' => 'decimal:3',
            'initial_charge_percent' => 'decimal:2',
            'electricity_kwh' => 'decimal:3',
            'rental_amount' => 'decimal:2',
            'electricity_amount' => 'decimal:2',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id', 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'user_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id', 'vehicle_id');
    }

    public function garage(): BelongsTo
    {
        return $this->belongsTo(Garage::class, 'garage_id', 'garage_id');
    }

    public function previousRental(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'previous_rental_id',
            'rental_id',
        );
    }

    public function nextRental(): HasOne
    {
        return $this->hasOne(
            self::class,
            'previous_rental_id',
            'rental_id',
        );
    }
}
