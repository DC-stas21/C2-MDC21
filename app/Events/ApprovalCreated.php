<?php

namespace App\Events;

use App\Models\Approval;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ApprovalCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $approvalId,
        public readonly string $action,
        public readonly string $level,
        public readonly string $reason
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('c2-dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'approval.created';
    }

    public function broadcastWith(): array
    {
        return [
            'approval_id' => $this->approvalId,
            'action' => $this->action,
            'level' => $this->level,
            'reason' => $this->reason,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public static function fromApproval(Approval $approval): self
    {
        return new self(
            approvalId: $approval->id,
            action: $approval->action,
            level: $approval->level,
            reason: $approval->reason
        );
    }
}
