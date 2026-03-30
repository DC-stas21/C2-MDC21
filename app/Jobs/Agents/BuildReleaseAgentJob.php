<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;
use App\Models\Approval;
use App\Models\NicheConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class BuildReleaseAgentJob extends BaseAgentJob
{
    public int $timeout = 300;

    public function __construct(
        private readonly string $nicheConfigId,
        private readonly string $environment = 'staging'
    ) {
        $this->onQueue('agents-ops');
    }

    protected function agentType(): string
    {
        return 'build_release';
    }

    protected function input(): array
    {
        return ['niche_config_id' => $this->nicheConfigId, 'environment' => $this->environment];
    }

    protected function execute(AgentRun $run): void
    {
        $niche = NicheConfig::findOrFail($this->nicheConfigId);
        $steps = [];

        $steps['validate_config'] = $this->validateConfig($niche);
        if ($steps['validate_config']['status'] === 'failed') {
            $this->updateOutput(['steps' => $steps, 'result' => 'failed']);
            throw new \RuntimeException('Config validation failed: '.$steps['validate_config']['message']);
        }

        $steps['pint'] = $this->runPint();
        $steps['tests'] = $this->runTests();

        if ($steps['tests']['status'] === 'failed') {
            $this->updateOutput(['steps' => $steps, 'result' => 'blocked', 'reason' => 'Tests failed']);
            throw new \RuntimeException('Tests failed — deploy blocked');
        }

        if ($this->environment === 'production') {
            Approval::create([
                'agent_run_id' => $run->id,
                'action' => "Deploy a producción: {$niche->domain}",
                'level' => 'N3',
                'status' => 'pending',
                'reason' => 'Deploy a producción siempre requiere aprobación humana',
                'context' => ['domain' => $niche->domain, 'tests' => $steps['tests']['status']],
            ]);
        }

        $this->updateOutput([
            'steps' => $steps,
            'result' => $this->environment === 'production' ? 'pending_approval' : 'success',
            'environment' => $this->environment,
            'domain' => $niche->domain,
        ]);

        Log::info("[build_release] {$this->environment} ready", ['domain' => $niche->domain]);
    }

    private function validateConfig(NicheConfig $niche): array
    {
        $errors = [];
        if (empty($niche->domain)) {
            $errors[] = 'Domain empty';
        }
        if (! $niche->is_active) {
            $errors[] = 'Niche inactive';
        }

        return ['status' => empty($errors) ? 'passed' : 'failed', 'message' => implode(', ', $errors) ?: 'Valid'];
    }

    private function runPint(): array
    {
        try {
            $result = Process::timeout(60)->run('./vendor/bin/pint --test');

            return ['status' => $result->successful() ? 'passed' : 'failed'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function runTests(): array
    {
        try {
            $result = Process::timeout(120)->run('./vendor/bin/pest --no-interaction');
            preg_match('/Tests:\s+(\d+)\s+passed/', $result->output(), $m);

            return ['status' => $result->successful() ? 'passed' : 'failed', 'passed' => (int) ($m[1] ?? 0)];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
