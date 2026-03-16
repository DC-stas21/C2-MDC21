<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Approval extends Model
{
    use HasUuids;

    protected $fillable = [
        'agent_run_id',
        'action',
        'level',
        'status',
        'requested_by',
        'decided_by',
        'reason',
        'decision_note',
        'context',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'decided_at' => 'datetime',
        ];
    }

    public function agentRun(): BelongsTo
    {
        return $this->belongsTo(AgentRun::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
