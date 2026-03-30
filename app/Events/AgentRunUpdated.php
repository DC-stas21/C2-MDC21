<?php

namespace App\Events;

use App\Models\AgentRun;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AgentRunUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $agentRunId,
        public readonly string $agentType,
        public readonly string $status,
        public readonly ?string $error = null
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('c2-dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'agent.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'agent_run_id' => $this->agentRunId,
            'agent_type' => $this->agentType,
            'status' => $this->status,
            'error' => $this->error,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    public static function fromRun(AgentRun $run): self
    {
        return new self(
            agentRunId: $run->id,
            agentType: $run->agent_type,
            status: $run->status,
            error: $run->error
        );
    }
}
