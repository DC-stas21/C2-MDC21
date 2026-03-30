<?php

namespace App\Jobs\Agents;

use App\Events\AgentRunUpdated;
use App\Models\AgentRun;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

abstract class BaseAgentJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 300;

    protected AgentRun $agentRun;

    abstract protected function agentType(): string;

    abstract protected function execute(AgentRun $run): void;

    public function handle(): void
    {
        $this->agentRun = AgentRun::create([
            'agent_type' => $this->agentType(),
            'status' => 'running',
            'input' => $this->input(),
            'started_at' => now(),
        ]);

        try {
            $this->execute($this->agentRun);

            $this->agentRun->update([
                'status' => 'completed',
                'finished_at' => now(),
            ]);

            AgentRunUpdated::dispatch(
                $this->agentRun->id,
                $this->agentRun->agent_type,
                'completed'
            );
        } catch (Throwable $e) {
            $this->agentRun->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            AgentRunUpdated::dispatch(
                $this->agentRun->id,
                $this->agentRun->agent_type,
                'failed',
                $e->getMessage()
            );

            Log::error("[{$this->agentType()}] Job failed", [
                'agent_run_id' => $this->agentRun->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    protected function input(): array
    {
        return [];
    }

    protected function updateOutput(array $output): void
    {
        $this->agentRun->update(['output' => $output]);
    }

    protected function updateMetadata(array $metadata): void
    {
        $existing = $this->agentRun->metadata ?? [];
        $this->agentRun->update(['metadata' => array_merge($existing, $metadata)]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error("[{$this->agentType()}] All retries exhausted", [
            'error' => $exception->getMessage(),
        ]);
    }
}
