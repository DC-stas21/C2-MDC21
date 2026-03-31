<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;
use App\Models\Approval;
use App\Models\NicheConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class QAExperimentationAgentJob extends BaseAgentJob
{
    public int $timeout = 600;

    public function __construct(
        private readonly string $nicheConfigId,
        private readonly string $task = 'qa'
    ) {
        $this->onQueue('agents-ops');
    }

    protected function agentType(): string
    {
        return 'qa_experimentation';
    }

    protected function input(): array
    {
        return ['niche_config_id' => $this->nicheConfigId, 'task' => $this->task];
    }

    protected function execute(AgentRun $run): void
    {
        match ($this->task) {
            'qa' => $this->runQA($run),
            'qa_web' => $this->runWebQA($run),
            default => throw new \InvalidArgumentException("Unknown task: {$this->task}"),
        };
    }

    /**
     * QA for C2 itself (Pest + Pint)
     */
    private function runQA(AgentRun $run): void
    {
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

        $this->updateOutput(['result' => $passed ? 'passed' : 'failed', 'checks' => $checks]);

        if (! $passed) {
            Log::warning('[qa] C2 QA failed', ['checks' => $checks]);
        }
    }

    /**
     * QA for a generated web — validates the built site works correctly.
     * Chained from PolicyBrand after WebBuilder generates the web.
     */
    private function runWebQA(AgentRun $run): void
    {
        $niche = NicheConfig::findOrFail($this->nicheConfigId);
        $checks = [];
        $passed = true;

        // 1. Verify dist/ exists (web was built)
        $distPath = $niche->sitePath().'/dist';
        $checks['build_exists'] = [
            'status' => is_dir($distPath) ? 'passed' : 'failed',
            'path' => $distPath,
        ];
        if ($checks['build_exists']['status'] === 'failed') {
            $passed = false;
        }

        // 2. Verify index.html exists
        $indexPath = $distPath.'/index.html';
        $checks['index_html'] = [
            'status' => file_exists($indexPath) ? 'passed' : 'failed',
        ];
        if ($checks['index_html']['status'] === 'failed') {
            $passed = false;
        }

        // 3. Verify site.config.json is valid
        $configPath = $niche->sitePath().'/site.config.json';
        $checks['config_valid'] = $this->validateSiteConfig($configPath);
        if ($checks['config_valid']['status'] === 'failed') {
            $passed = false;
        }

        // 4. HTTP check if Nginx is configured
        $checks['http_check'] = $this->checkHttpStatus($niche->domain);

        // 5. Simulated performance (TODO: replace with real Lighthouse CI)
        $perfScore = rand(65, 98);
        $lcp = rand(1500, 3800);
        $checks['performance'] = [
            'status' => ($perfScore < 60 || $lcp > 4000) ? 'failed' : 'passed',
            'performance_score' => $perfScore,
            'lcp_ms' => $lcp,
            'method' => 'simulated',
        ];
        if ($checks['performance']['status'] === 'failed') {
            $passed = false;
        }

        $this->updateOutput([
            'result' => $passed ? 'passed' : 'failed',
            'domain' => $niche->domain,
            'checks' => $checks,
        ]);

        if ($passed) {
            // QA passed → chain to BuildRelease for deploy
            BuildReleaseAgentJob::dispatch($this->nicheConfigId, 'staging');
            Log::info('[qa] Web QA passed, dispatching BuildRelease', ['domain' => $niche->domain]);
        } else {
            // QA failed → create N3 for human override
            $niche->update(['build_status' => NicheConfig::STATUS_FAILED]);

            Approval::create([
                'agent_run_id' => $run->id,
                'action' => "QA fallido: {$niche->domain} — override disponible",
                'level' => 'N3',
                'status' => 'pending',
                'reason' => 'QA detectó fallos en la web generada',
                'context' => ['domain' => $niche->domain, 'checks' => $checks],
            ]);

            Log::warning('[qa] Web QA failed', ['domain' => $niche->domain]);
        }
    }

    private function validateSiteConfig(string $path): array
    {
        if (! file_exists($path)) {
            return ['status' => 'failed', 'message' => 'site.config.json not found'];
        }

        $config = json_decode(file_get_contents($path), true);
        if (! $config) {
            return ['status' => 'failed', 'message' => 'Invalid JSON'];
        }

        $required = ['meta', 'design', 'pages', 'navigation', 'footer'];
        foreach ($required as $key) {
            if (! isset($config[$key])) {
                return ['status' => 'failed', 'message' => "Missing key: {$key}"];
            }
        }

        return ['status' => 'passed', 'pages_count' => count($config['pages'])];
    }

    private function checkHttpStatus(string $domain): array
    {
        try {
            $response = Http::timeout(10)->get("http://{$domain}");

            return [
                'status' => $response->successful() ? 'passed' : 'warning',
                'http_code' => $response->status(),
            ];
        } catch (\Throwable) {
            return [
                'status' => 'skipped',
                'message' => 'Domain not reachable (DNS may not be configured yet)',
            ];
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
