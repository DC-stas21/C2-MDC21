<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class NicheConfig extends Model
{
    use HasUuids;

    protected $fillable = [
        'domain',
        'vertical',
        'cpl',
        'colors',
        'config',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cpl' => 'decimal:2',
            'colors' => 'array',
            'config' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
