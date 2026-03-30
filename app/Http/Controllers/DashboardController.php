<?php

namespace App\Http\Controllers;

use App\Models\AgentRun;
use App\Models\Approval;
use App\Models\Lead;
use App\Models\NicheConfig;
use App\Services\ScoreComposite;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => $this->getStats(),
            'agentStatuses' => $this->getAgentStatuses(),
            'pendingApprovals' => $this->getPendingApprovals(),
            'agentActivity' => $this->getAgentActivity(),
            'assets' => $this->getAssets(),
            'timeline' => $this->getTimeline(),
        ]);
    }

    private function getStats(): array
    {
        return [
            'agents_active' => AgentRun::where('status', 'running')->count(),
            'agents_completed_today' => AgentRun::where('status', 'completed')->whereDate('finished_at', today())->count(),
            'agents_failed_today' => AgentRun::where('status', 'failed')->whereDate('finished_at', today())->count(),
            'agents_total_week' => AgentRun::where('created_at', '>=', now()->subDays(7))->count(),
            'approvals_pending' => Approval::where('status', 'pending')->count(),
            'approvals_resolved_today' => Approval::whereIn('status', ['approved', 'denied'])->whereDate('decided_at', today())->count(),
            'assets_total' => NicheConfig::where('is_active', true)->count(),
            // Leads desactivado hasta implementación futura
            'score_avg' => $this->calculatePortfolioScore(),
            'success_rate' => $this->getSuccessRate(),
        ];
    }

    private function getAgentStatuses(): array
    {
        $agents = [
            'orchestrator', 'policy_brand', 'seo_content', 'distribution',
            'engagement_retention', 'monetization_leads', 'build_release',
            'infra_reliability', 'qa_experimentation',
        ];

        return collect($agents)->map(function (string $type) {
            $lastRun = AgentRun::where('agent_type', $type)
                ->latest('created_at')
                ->first(['status', 'started_at', 'finished_at', 'error']);

            $todayCount = AgentRun::where('agent_type', $type)
                ->whereDate('created_at', today())
                ->count();

            $todayFailed = AgentRun::where('agent_type', $type)
                ->where('status', 'failed')
                ->whereDate('created_at', today())
                ->count();

            $isRunning = AgentRun::where('agent_type', $type)
                ->where('status', 'running')
                ->exists();

            return [
                'type' => $type,
                'is_running' => $isRunning,
                'last_status' => $lastRun?->status,
                'last_run_at' => $lastRun?->started_at,
                'last_error' => $lastRun?->error,
                'today_runs' => $todayCount,
                'today_failed' => $todayFailed,
            ];
        })->all();
    }

    private function getPendingApprovals(): array
    {
        return Approval::with('agentRun:id,agent_type')
            ->where('status', 'pending')
            ->latest()
            ->take(10)
            ->get()
            ->toArray();
    }

    private function getAgentActivity(): array
    {
        return AgentRun::selectRaw('DATE(created_at) as date, agent_type, status, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupByRaw('DATE(created_at), agent_type, status')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getAssets(): array
    {
        return NicheConfig::where('is_active', true)
            ->get(['id', 'domain', 'vertical', 'cpl'])
            ->map(function (NicheConfig $niche) {
                $score = Cache::get("score_composite:{$niche->id}");

                return [
                    'id' => $niche->id,
                    'domain' => $niche->domain,
                    'vertical' => $niche->vertical,
                    'cpl' => $niche->cpl,
                    'score' => $score,
                    'classification' => $score !== null ? app(ScoreComposite::class)->classify($score) : null,
                ];
            })
            ->all();
    }

    private function getTimeline(): array
    {
        $runs = AgentRun::latest('created_at')
            ->take(15)
            ->get(['id', 'agent_type', 'status', 'started_at', 'finished_at', 'error', 'created_at']);

        $approvals = Approval::with('agentRun:id,agent_type')
            ->whereIn('status', ['approved', 'denied'])
            ->latest('decided_at')
            ->take(10)
            ->get(['id', 'action', 'status', 'level', 'decided_at', 'agent_run_id']);

        $events = collect();

        foreach ($runs as $run) {
            $events->push([
                'type' => 'agent_run',
                'agent_type' => $run->agent_type,
                'status' => $run->status,
                'error' => $run->error,
                'at' => $run->started_at ?? $run->created_at,
            ]);
        }

        foreach ($approvals as $a) {
            $events->push([
                'type' => 'approval',
                'action' => $a->action,
                'status' => $a->status,
                'level' => $a->level,
                'agent_type' => $a->agentRun?->agent_type,
                'at' => $a->decided_at,
            ]);
        }

        return $events->sortByDesc('at')->take(15)->values()->all();
    }

    private function getLeadsByStatus(): array
    {
        return Lead::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    private function calculatePortfolioScore(): ?float
    {
        $niches = NicheConfig::where('is_active', true)->pluck('id');
        if ($niches->isEmpty()) {
            return null;
        }

        $scores = $niches->map(fn (string $id) => Cache::get("score_composite:{$id}"))->filter();

        return $scores->isEmpty() ? null : round($scores->avg(), 1);
    }

    private function getSuccessRate(): ?float
    {
        $total = AgentRun::where('created_at', '>=', now()->subDays(7))
            ->whereIn('status', ['completed', 'failed'])
            ->count();

        if ($total === 0) {
            return null;
        }

        $completed = AgentRun::where('created_at', '>=', now()->subDays(7))
            ->where('status', 'completed')
            ->count();

        return round(($completed / $total) * 100, 1);
    }
}
