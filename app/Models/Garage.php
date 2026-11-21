<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'garage_type_id',
    'code',
    'daily_rate',
    'electricity_rate',
])]
class Garage extends Model
{
    protected $primaryKey = 'garage_id';

    protected function casts(): array
    {
        return [
            'daily_rate' => 'decimal:2',
            'electricity_rate' => 'decimal:2',
        ];
    }

    public function garageType(): BelongsTo
    {
        return $this->belongsTo(
            GarageType::class,
            'garage_type_id',
            'garage_type_id',
        );
    }
}