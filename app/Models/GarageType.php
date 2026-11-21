<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'prefix'])]
class GarageType extends Model
{
    protected $primaryKey = 'garage_type_id';

    protected function casts(): array
    {
        return [
            'last_number' => 'integer',
        ];
    }
}