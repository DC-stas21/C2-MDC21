<?php

namespace App\Http\Controllers;

use App\Models\AgentRun;
use App\Models\Approval;
use App\Models\NicheConfig;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Core agents for the web factory pipeline.
     */
    private const PIPELINE_AGENTS = [
        'orchestrator', 'web_builder', 'policy_brand',
        'qa_experimentation', 'build_release', 'infra_reliability',
    ];

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
        $week = now()->subDays(7);
        $totalWeek = AgentRun::where('created_at', '>=', $week)->whereIn('status', ['completed', 'failed'])->count();
        $completedWeek = AgentRun::where('created_at', '>=', $week)->where('status', 'completed')->count();

        return [
            'agents_active' => AgentRun::where('status', 'running')->count(),
            'agents_completed_today' => AgentRun::where('status', 'completed')->whereDate('finished_at', today())->count(),
            'agents_failed_today' => AgentRun::where('status', 'failed')->whereDate('finished_at', today())->count(),
            'approvals_pending' => Approval::where('status', 'pending')->count(),
            'webs_total' => NicheConfig::where('is_active', true)->count(),
            'webs_live' => NicheConfig::where('build_status', NicheConfig::STATUS_LIVE)->count(),
            'webs_building' => NicheConfig::where('build_status', NicheConfig::STATUS_BUILDING)->count(),
            'webs_staging' => NicheConfig::where('build_status', NicheConfig::STATUS_STAGING)->count(),
            'webs_failed' => NicheConfig::where('build_status', NicheConfig::STATUS_FAILED)->count(),
            'success_rate' => $totalWeek > 0 ? round(($completedWeek / $totalWeek) * 100, 1) : null,
        ];
    }

    private function getAgentStatuses(): array
    {
        return collect(self::PIPELINE_AGENTS)->map(function (string $type) {
            $lastRun = AgentRun::where('agent_type', $type)
                ->latest('created_at')
                ->first(['status', 'started_at', 'finished_at', 'error']);

            $isRunning = AgentRun::where('agent_type', $type)->where('status', 'running')->exists();
            $todayRuns = AgentRun::where('agent_type', $type)->whereDate('created_at', today())->count();
            $todayFailed = AgentRun::where('agent_type', $type)->where('status', 'failed')->whereDate('created_at', today())->count();

            return [
                'type' => $type,
                'is_running' => $isRunning,
                'last_status' => $lastRun?->status,
                'last_run_at' => $lastRun?->started_at,
                'last_error' => $lastRun?->error,
                'today_runs' => $todayRuns,
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
            ->get(['id', 'domain', 'vertical', 'build_status', 'build_metadata', 'config'])
            ->map(function (NicheConfig $niche) {
                return [
                    'id' => $niche->id,
                    'domain' => $niche->domain,
                    'vertical' => $niche->vertical,
                    'build_status' => $niche->build_status,
                    'description' => $niche->config['description'] ?? '',
                    'last_build' => $niche->build_metadata['last_build_at'] ?? null,
                    'error' => $niche->build_metadata['error'] ?? null,
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
}
