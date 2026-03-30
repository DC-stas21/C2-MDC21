<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;
use App\Models\Approval;
use App\Models\NicheConfig;
use App\Services\ScoreComposite;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class OrchestratorAgentJob extends BaseAgentJob
{
    public int $timeout = 600;

    public function __construct(
        private readonly array $assetIds = []
    ) {
        $this->onQueue('agents');
    }

    protected function agentType(): string
    {
        return 'orchestrator';
    }

    protected function input(): array
    {
        return ['asset_ids' => $this->assetIds];
    }

    protected function execute(AgentRun $run): void
    {
        $scoreComposite = app(ScoreComposite::class);

        $assets = $this->assetIds
            ? NicheConfig::whereIn('id', $this->assetIds)->where('is_active', true)->get()
            : NicheConfig::where('is_active', true)->get();

        $assetReports = [];
        $actions = [];
        $alerts = [];

        foreach ($assets as $asset) {
            $metrics = $this->gatherMetrics($asset);
            $score = $scoreComposite->calculate($asset->id, $metrics);
            $classification = $scoreComposite->classify($score);
            $assetAlerts = $scoreComposite->detectAlerts($asset->id, $metrics);

            $assetReports[] = [
                'domain' => $asset->domain,
                'vertical' => $asset->vertical,
                'score' => $score,
                'classification' => $classification,
                'alerts' => $assetAlerts,
            ];

            if ($score < 20) {
                $actions[] = ['level' => 'N3', 'action' => "CRITICAL: {$asset->domain} score {$score}", 'asset' => $asset->domain];
            } elseif ($score < 40) {
                $actions[] = ['level' => 'N2', 'action' => "WARNING: {$asset->domain} score {$score}", 'asset' => $asset->domain];
            }

            foreach ($assetAlerts as $alert) {
                $level = $alert['severity'] === 'critical' ? 'N3' : 'N2';
                $actions[] = ['level' => $level, 'action' => "{$asset->domain}: {$alert['message']}", 'asset' => $asset->domain];
            }

            $alerts = array_merge($alerts, $assetAlerts);
        }

        foreach ($actions as $action) {
            if ($action['level'] === 'N3') {
                Approval::create([
                    'agent_run_id' => $run->id,
                    'action' => $action['action'],
                    'level' => 'N3',
                    'status' => 'pending',
                    'reason' => 'Orquestador detectó situación crítica',
                    'context' => ['asset' => $action['asset']],
                ]);
            }
        }

        $avgScore = count($assetReports) > 0 ? round(collect($assetReports)->avg('score'), 1) : null;

        Cache::put('orchestrator:last_report', [
            'timestamp' => now()->toIso8601String(),
            'assets_analyzed' => count($assetReports),
            'avg_score' => $avgScore,
            'total_alerts' => count($alerts),
        ], 86400);

        $this->updateOutput([
            'assets_analyzed' => count($assetReports),
            'portfolio_avg_score' => $avgScore,
            'asset_reports' => $assetReports,
            'actions' => $actions,
            'total_alerts' => count($alerts),
            'n3_created' => collect($actions)->where('level', 'N3')->count(),
        ]);

        Log::info('[orchestrator] Cycle completed', ['assets' => count($assetReports), 'avg_score' => $avgScore]);
    }

    private function gatherMetrics(NicheConfig $asset): array
    {
        // TODO: Replace with real data from Umami, GSC, etc.
        $cachedScore = Cache::get("score_composite:{$asset->id}");

        return [
            'traffic' => ['sessions' => $cachedScore ? (int) ($cachedScore * 12) : 500, 'baseline' => 1000],
            'engagement' => ['avg_time' => $cachedScore ? (int) ($cachedScore * 2.5) : 120],
            'revenue' => ['amount' => $cachedScore ? (int) ($cachedScore * 8) : 400, 'target' => 1000],
            'quality' => ['score' => $cachedScore ? (int) $cachedScore : 50],
            'trend' => ['change_pct' => rand(-15, 25)],
            'core_web_vitals' => [
                'performance' => $cachedScore ? min(100, (int) ($cachedScore * 1.1)) : 70,
                'lcp' => $cachedScore && $cachedScore > 60 ? 2200 : 3500,
            ],
        ];
    }
}
