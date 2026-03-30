<?php

use App\Jobs\Agents\BaseAgentJob;
use App\Models\AgentRun;

// Create a concrete test implementation
class TestAgentJob extends BaseAgentJob
{
    private bool $shouldFail;

    public function __construct(bool $shouldFail = false)
    {
        $this->shouldFail = $shouldFail;
        $this->onQueue('agents');
    }

    protected function agentType(): string
    {
        return 'orchestrator';
    }

    protected function input(): array
    {
        return ['test' => true];
    }

    protected function execute(AgentRun $run): void
    {
        if ($this->shouldFail) {
            throw new RuntimeException('Test failure');
        }

        $this->updateOutput(['result' => 'success']);
        $this->updateMetadata(['processed' => 42]);
    }
}

test('base agent job creates agent_run on execution', function () {
    $job = new TestAgentJob;
    $job->handle();

    $run = AgentRun::latest()->first();

    expect($run)->not()->toBeNull();
    expect($run->agent_type)->toBe('orchestrator');
    expect($run->status)->toBe('completed');
    expect($run->input)->toBe(['test' => true]);
    expect($run->output)->toBe(['result' => 'success']);
    expect($run->metadata)->toHaveKey('processed', 42);
    expect($run->started_at)->not()->toBeNull();
    expect($run->finished_at)->not()->toBeNull();
});

test('base agent job records failure', function () {
    $job = new TestAgentJob(shouldFail: true);

    try {
        $job->handle();
    } catch (RuntimeException) {
        // Expected
    }

    $run = AgentRun::latest()->first();

    expect($run->status)->toBe('failed');
    expect($run->error)->toBe('Test failure');
    expect($run->finished_at)->not()->toBeNull();
});
