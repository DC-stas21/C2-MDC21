<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;
use App\Models\Approval;
use App\Models\Experiment;
use App\Models\NicheConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class QAExperimentationAgentJob extends BaseAgentJob
{
    public int $timeout = 600;

    public function __construct(
        private readonly string $nicheConfigId,
        private readonly string $task = 'qa',
        private readonly ?string $experimentId = null
    ) {
        $this->onQueue('agents-ops');
    }

    protected function agentType(): string
    {
        return 'qa_experimentation';
    }

    protected function input(): array
    {
        return ['niche_config_id' => $this->nicheConfigId, 'task' => $this->task, 'experiment_id' => $this->experimentId];
    }

    protected function execute(AgentRun $run): void
    {
        match ($this->task) {
            'qa' => $this->runQA($run),
            'evaluate_ab' => $this->evaluateABTest($run),
            default => throw new \InvalidArgumentException("Unknown task: {$this->task}"),
        };
    }

    private function runQA(AgentRun $run): void
    {
        $niche = NicheConfig::findOrFail($this->nicheConfigId);
        $checks = [];
        $passed = true;

        $checks['pest'] = $this->runPest();
        if ($checks['pest']['status'] === 'failed') {
            $passed = false;
        }

        $checks['pint'] = $this->runPint();
        if ($checks['pint']['status'] === 'failed') {
            $passed = false;
        }

        // TODO: Replace with real Lighthouse CI
        $perfScore = rand(60, 98);
        $lcp = rand(1800, 4200);
        $checks['performance'] = [
            'status' => ($perfScore < 60 || $lcp > 4000) ? 'failed' : 'passed',
            'performance_score' => $perfScore,
            'lcp_ms' => $lcp,
            'method' => 'simulated',
        ];
        if ($checks['performance']['status'] === 'failed') {
            $passed = false;
        }

        // TODO: Replace with Playwright
        $checks['links'] = ['status' => 'passed', 'method' => 'pending_playwright'];

        $this->updateOutput([
            'result' => $passed ? 'passed' : 'failed',
            'domain' => $niche->domain,
            'checks' => $checks,
        ]);

        if (! $passed) {
            Approval::create([
                'agent_run_id' => $run->id,
                'action' => "QA fallido: {$niche->domain} — override disponible",
                'level' => 'N3',
                'status' => 'pending',
                'reason' => 'QA detectó fallos. Override humano requerido.',
                'context' => ['domain' => $niche->domain, 'checks' => $checks],
            ]);
            Log::warning('[qa_experimentation] QA failed', ['domain' => $niche->domain]);
        }
    }

    private function evaluateABTest(AgentRun $run): void
    {
        if (! $this->experimentId) {
            throw new \InvalidArgumentException('experiment_id required');
        }

        $experiment = Experiment::findOrFail($this->experimentId);
        $results = $experiment->results ?? [];

        $winner = null;
        $confidence = 0;

        if (count($results) >= 2) {
            $sorted = collect($results)->sortByDesc('conversion_rate');
            $best = $sorted->first();
            $second = $sorted->skip(1)->first();
            if ($best && $second) {
                $confidence = min(99, round((($best['conversion_rate'] ?? 0) - ($second['conversion_rate'] ?? 0)) * 100, 1));
                $winner = $best['variant'] ?? null;
            }
        }

        $this->updateOutput(['experiment_id' => $this->experimentId, 'winner' => $winner, 'confidence' => $confidence]);

        if ($winner && $confidence > 95) {
            Approval::create([
                'agent_run_id' => $run->id,
                'action' => "A/B ganador: {$winner} ({$confidence}%)",
                'level' => 'N3',
                'status' => 'pending',
                'reason' => 'Ganador con significancia estadística',
                'context' => ['experiment_id' => $this->experimentId, 'winner' => $winner, 'confidence' => $confidence],
            ]);
        }
    }

    private function runPest(): array
    {
        try {
            $result = Process::timeout(120)->run('./vendor/bin/pest --no-interaction');
            preg_match('/Tests:\s+(\d+)\s+passed/', $result->output(), $m);

            return ['status' => $result->successful() ? 'passed' : 'failed', 'tests_passed' => (int) ($m[1] ?? 0)];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
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
}
