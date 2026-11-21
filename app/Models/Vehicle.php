<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'type',
    'plate_number',
    'brand',
    'model',
    'battery_capacity_kwh',
])]
class Vehicle extends Model
{
    protected $primaryKey = 'vehicle_id';

    protected function casts(): array
    {
        return [
            'battery_capacity_kwh' => 'decimal:3',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'user_id',
        );
    }
}