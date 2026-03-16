<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentRun extends Model
{
    use HasUuids;

    protected $fillable = [
        'agent_type',
        'status',
        'input',
        'output',
        'metadata',
        'started_at',
        'finished_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'input' => 'array',
            'output' => 'array',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(Approval::class);
    }

    public function artifacts(): HasMany
    {
        return $this->hasMany(Artifact::class);
    }
}
