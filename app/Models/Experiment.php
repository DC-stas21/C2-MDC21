<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Experiment extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'asset',
        'variants',
        'metric',
        'results',
        'winner',
        'status',
        'confirmed',
    ];

    protected function casts(): array
    {
        return [
            'variants' => 'array',
            'results' => 'array',
            'confirmed' => 'boolean',
        ];
    }
}
