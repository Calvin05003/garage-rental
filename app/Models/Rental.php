<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'tenant_id',
    'created_by',
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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'rental_id', 'rental_id');
    }

    public function scopeActiveOn(Builder $query, string $date): Builder
    {
        return $query->whereDate('start_date', '<=', $date)->whereDate('end_date', '>', $date);
    }

    public function isActiveOn(CarbonInterface $date): bool
    {
        $day = $date->toDateString();

        return $this->start_date->toDateString() <= $day && $this->end_date->toDateString() > $day;
    }
}
