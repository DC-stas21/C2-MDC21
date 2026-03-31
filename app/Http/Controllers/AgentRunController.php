<?php

namespace App\Http\Controllers;

use App\Models\AgentRun;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AgentRunController extends Controller
{
    private const AGENT_TYPES = [
        'orchestrator', 'web_builder', 'policy_brand', 'seo_content',
        'distribution', 'build_release', 'infra_reliability', 'qa_experimentation',
    ];

    private const AGENT_LAYERS = [
        'orchestrator' => 0, 'web_builder' => 1, 'policy_brand' => 0,
        'seo_content' => 1, 'distribution' => 1,
        'build_release' => 2, 'infra_reliability' => 2, 'qa_experimentation' => 2,
    ];

    private const AGENT_MODELS = [
        'orchestrator' => 'Claude Sonnet',
        'web_builder' => 'Claude Sonnet',
        'policy_brand' => 'Claude Haiku',
        'seo_content' => 'GPT-4o + GPT-4o-mini',
        'distribution' => 'Claude Sonnet + GPT-4o',
        'build_release' => 'Script (sin IA)',
        'infra_reliability' => 'Script (sin IA)',
        'qa_experimentation' => 'Pest + Lighthouse',
    ];

    private const AGENT_QUEUES = [
        'orchestrator' => 'agents', 'web_builder' => 'agents-ops',
        'policy_brand' => 'agents', 'seo_content' => 'agents',
        'distribution' => 'agents',
        'build_release' => 'agents-ops', 'infra_reliability' => 'agents-ops',
        'qa_experimentation' => 'agents-ops',
    ];

    public function index(Request $request): Response
    {
        $query = AgentRun::latest();

        if ($request->filled('agent_type')) {
            $query->where('agent_type', $request->input('agent_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $runs = $query->paginate(20)->withQueryString();

        return Inertia::render('AgentRuns/Index', [
            'runs' => $runs,
            'filters' => $request->only(['agent_type', 'status']),
            'globalStats' => $this->getGlobalStats(),
            'agentProfiles' => $this->getAgentProfiles(),
            'dailyActivity' => $this->getDailyActivity(),
            'recentErrors' => $this->getRecentErrors(),
        ]);
    }

    private function getGlobalStats(): array
    {
        $week = now()->subDays(7);
        $totalWeek = AgentRun::where('created_at', '>=', $week)->whereIn('status', ['completed', 'failed'])->count();
        $completedWeek = AgentRun::where('created_at', '>=', $week)->where('status', 'completed')->count();

        return [
            'running' => AgentRun::where('status', 'running')->count(),
            'pending' => AgentRun::where('status', 'pending')->count(),
            'completed_today' => AgentRun::where('status', 'completed')->whereDate('finished_at', today())->count(),
            'failed_today' => AgentRun::where('status', 'failed')->whereDate('finished_at', today())->count(),
            'total_week' => AgentRun::where('created_at', '>=', $week)->count(),
            'success_rate' => $totalWeek > 0 ? round(($completedWeek / $totalWeek) * 100, 1) : null,
            'avg_duration' => $this->getAvgDuration(),
        ];
    }

    private function getAgentProfiles(): array
    {
        return collect(self::AGENT_TYPES)->map(function (string $type) {
            $week = now()->subDays(7);
            $totalWeek = AgentRun::where('agent_type', $type)->where('created_at', '>=', $week)->whereIn('status', ['completed', 'failed'])->count();
            $completedWeek = AgentRun::where('agent_type', $type)->where('created_at', '>=', $week)->where('status', 'completed')->count();

            $lastRun = AgentRun::where('agent_type', $type)->latest('created_at')
                ->first(['status', 'started_at', 'finished_at', 'error']);

            $avgDuration = AgentRun::where('agent_type', $type)
                ->where('status', 'completed')
                ->whereNotNull('started_at')
                ->whereNotNull('finished_at')
                ->where('created_at', '>=', $week)
                ->selectRaw('AVG(EXTRACT(EPOCH FROM (finished_at - started_at))) as avg_sec')
                ->value('avg_sec');

            $todayRuns = AgentRun::where('agent_type', $type)->whereDate('created_at', today())->count();
            $todayFailed = AgentRun::where('agent_type', $type)->where('status', 'failed')->whereDate('created_at', today())->count();
            $isRunning = AgentRun::where('agent_type', $type)->where('status', 'running')->exists();

            return [
                'type' => $type,
                'layer' => self::AGENT_LAYERS[$type],
                'model' => self::AGENT_MODELS[$type],
                'queue' => self::AGENT_QUEUES[$type],
                'is_running' => $isRunning,
                'last_status' => $lastRun?->status,
                'last_run_at' => $lastRun?->started_at,
                'last_error' => $lastRun?->error,
                'today_runs' => $todayRuns,
                'today_failed' => $todayFailed,
                'week_total' => $totalWeek + AgentRun::where('agent_type', $type)->where('created_at', '>=', $week)->whereIn('status', ['running', 'pending'])->count(),
                'success_rate' => $totalWeek > 0 ? round(($completedWeek / $totalWeek) * 100, 1) : null,
                'avg_duration_sec' => $avgDuration ? round($avgDuration) : null,
            ];
        })->all();
    }

    private function getDailyActivity(): array
    {
        return AgentRun::selectRaw('DATE(created_at) as date, status, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupByRaw('DATE(created_at), status')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getRecentErrors(): array
    {
        return AgentRun::where('status', 'failed')
            ->whereNotNull('error')
            ->latest()
            ->take(5)
            ->get(['id', 'agent_type', 'error', 'started_at'])
            ->toArray();
    }

    private function getAvgDuration(): ?int
    {
        $avg = AgentRun::where('status', 'completed')
            ->whereNotNull('started_at')
            ->whereNotNull('finished_at')
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (finished_at - started_at))) as avg_sec')
            ->value('avg_sec');

        return $avg ? (int) round($avg) : null;
    }
}
