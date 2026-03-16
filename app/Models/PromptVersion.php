<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PromptVersion extends Model
{
    use HasUuids;

    protected $fillable = [
        'agent_type',
        'version',
        'model',
        'prompt_text',
        'metrics',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'metrics' => 'array',
            'is_active' => 'boolean',
        ];
    }
}
