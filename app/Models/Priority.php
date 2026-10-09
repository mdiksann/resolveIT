<?php

namespace App\Models;

use Database\Factories\PriorityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'rank', 'sla_hours', 'is_default', 'is_active'])]
class Priority extends Model
{
    /** @use HasFactory<PriorityFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'sla_hours' => 'integer',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
}
