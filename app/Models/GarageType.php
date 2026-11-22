<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'prefix'])]
class GarageType extends Model
{
    protected $primaryKey = 'garage_type_id';

    public function displayName(): string
    {
        return match ($this->prefix) {
            'S' => 'Bicycle',
            'C' => 'Gasoline car',
            'E' => 'Electric car',
            default => $this->name,
        };
    }

    protected function casts(): array
    {
        return [
            'last_number' => 'integer',
        ];
    }
}
