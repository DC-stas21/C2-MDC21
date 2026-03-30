<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InfraStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $severity,
        public readonly int $alertsCount,
        public readonly array $checks
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('c2-dashboard')];
    }

    public function broadcastAs(): string
    {
        return 'infra.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'severity' => $this->severity,
            'alerts_count' => $this->alertsCount,
            'checks' => $this->checks,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
