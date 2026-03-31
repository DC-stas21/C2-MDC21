<?php

namespace App\Http\Controllers;

use App\Jobs\Agents\BuildReleaseAgentJob;
use App\Jobs\Agents\WebBuilderAgentJob;
use App\Models\Approval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Approval::with(['agentRun:id,agent_type', 'requester:id,name', 'decider:id,name']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        } else {
            $query->where('status', 'pending');
        }

        if ($request->filled('level')) {
            $query->where('level', $request->input('level'));
        }

        if ($request->filled('agent_type')) {
            $query->whereHas('agentRun', function ($q) use ($request) {
                $q->where('agent_type', $request->input('agent_type'));
            });
        }

        $approvals = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Approvals/Index', [
            'approvals' => $approvals,
            'filters' => $request->only(['status', 'level', 'agent_type']),
            'stats' => $this->getStats(),
            'byLevel' => $this->getByLevel(),
            'byAgent' => $this->getByAgent(),
            'recentDecisions' => $this->getRecentDecisions(),
            'avgResponseTime' => $this->getAvgResponseTime(),
        ]);
    }

    public function approve(Request $request, Approval $approval): RedirectResponse
    {
        $approval->update([
            'status' => 'approved',
            'decided_by' => $request->user()?->id,
            'decision_note' => $request->input('note'),
            'decided_at' => now(),
        ]);

        activity()
            ->performedOn($approval)
            ->causedBy($request->user())
            ->withProperties(['action' => 'approved', 'note' => $request->input('note')])
            ->log('Approval approved');

        // If this is a production deploy approval, dispatch production deploy
        $domain = $approval->context['domain'] ?? null;
        if ($domain && str_contains($approval->action, 'Deploy a producción')) {
            $niche = NicheConfig::where('domain', $domain)->first();
            if ($niche && $niche->build_status === NicheConfig::STATUS_STAGING) {
                if (app()->environment('production')) {
                    // Production: dispatch BuildRelease → Nginx + SSL + health check
                    BuildReleaseAgentJob::dispatch($niche->id, 'production');
                    $niche->update(['build_status' => NicheConfig::STATUS_BUILDING]);
                } else {
                    // Local: mark as live directly (no Nginx)
                    $niche->update([
                        'build_status' => NicheConfig::STATUS_LIVE,
                        'build_metadata' => array_merge($niche->build_metadata ?? [], [
                            'deployed_at' => now()->toIso8601String(),
                            'approved_by' => $request->user()?->id,
                        ]),
                    ]);
                }
            }
        }

        return back()->with('success', 'Aprobado correctamente.');
    }

    public function deny(Request $request, Approval $approval): RedirectResponse
    {
        $approval->update([
            'status' => 'denied',
            'decided_by' => $request->user()?->id,
            'decision_note' => $request->input('note'),
            'decided_at' => now(),
        ]);

        activity()
            ->performedOn($approval)
            ->causedBy($request->user())
            ->withProperties(['action' => 'denied', 'note' => $request->input('note')])
            ->log('Approval denied');

        // If deploy denied with feedback → rebuild the web with the feedback
        $domain = $approval->context['domain'] ?? null;
        $feedback = $request->input('note');
        if ($domain && $feedback && str_contains($approval->action, 'Deploy')) {
            $niche = NicheConfig::where('domain', $domain)->first();
            if ($niche) {
                // Store feedback and re-trigger build
                $niche->update([
                    'build_status' => NicheConfig::STATUS_PENDING,
                    'build_metadata' => array_merge($niche->build_metadata ?? [], [
                        'feedback' => $feedback,
                        'feedback_at' => now()->toIso8601String(),
                        'feedback_by' => $request->user()?->name,
                    ]),
                ]);

                // Re-dispatch WebBuilder with feedback in the config
                $config = $niche->config ?? [];
                $config['rebuild_feedback'] = $feedback;
                $niche->update(['config' => $config]);

                WebBuilderAgentJob::dispatch($niche->id);
            }
        }

        return back()->with('success', 'Denegado. WebBuilder regenerará la web con tu feedback.');
    }

    private function getStats(): array
    {
        $today = today();

        return [
            'pending' => Approval::where('status', 'pending')->count(),
            'approved_today' => Approval::where('status', 'approved')->whereDate('decided_at', $today)->count(),
            'denied_today' => Approval::where('status', 'denied')->whereDate('decided_at', $today)->count(),
            'approved_week' => Approval::where('status', 'approved')->where('decided_at', '>=', now()->subDays(7))->count(),
            'denied_week' => Approval::where('status', 'denied')->where('decided_at', '>=', now()->subDays(7))->count(),
            'total' => Approval::count(),
            'oldest_pending_hours' => $this->getOldestPendingHours(),
        ];
    }

    private function getByLevel(): array
    {
        return Approval::where('status', 'pending')
            ->selectRaw('level, COUNT(*) as count')
            ->groupBy('level')
            ->pluck('count', 'level')
            ->toArray();
    }

    private function getByAgent(): array
    {
        return Approval::where('approvals.status', 'pending')
            ->join('agent_runs', 'approvals.agent_run_id', '=', 'agent_runs.id')
            ->selectRaw('agent_runs.agent_type, COUNT(*) as count')
            ->groupBy('agent_runs.agent_type')
            ->pluck('count', 'agent_type')
            ->toArray();
    }

    private function getRecentDecisions(): array
    {
        return Approval::with(['agentRun:id,agent_type', 'decider:id,name'])
            ->whereIn('status', ['approved', 'denied'])
            ->latest('decided_at')
            ->take(8)
            ->get(['id', 'action', 'status', 'level', 'decided_by', 'decision_note', 'decided_at', 'agent_run_id'])
            ->toArray();
    }

    private function getOldestPendingHours(): ?int
    {
        $oldest = Approval::where('status', 'pending')
            ->orderBy('created_at')
            ->value('created_at');

        if (! $oldest) {
            return null;
        }

        return (int) now()->diffInHours($oldest);
    }

    private function getAvgResponseTime(): ?float
    {
        $avg = Approval::whereIn('status', ['approved', 'denied'])
            ->whereNotNull('decided_at')
            ->where('decided_at', '>=', now()->subDays(7))
            ->selectRaw('AVG(EXTRACT(EPOCH FROM (decided_at - created_at)) / 3600) as avg_hours')
            ->value('avg_hours');

        return $avg ? round($avg, 1) : null;
    }
}
