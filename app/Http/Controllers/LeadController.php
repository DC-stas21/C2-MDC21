<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\NicheConfig;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Lead::latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('asset')) {
            $query->where('asset', $request->input('asset'));
        }

        if ($request->filled('score_min')) {
            $query->where('score', '>=', (int) $request->input('score_min'));
        }

        $leads = $query->paginate(25)->withQueryString();

        $pipeline = Lead::selectRaw('status, COUNT(*) as count, AVG(score) as avg_score')
            ->groupBy('status')
            ->get()
            ->keyBy('status')
            ->toArray();

        $byAsset = Lead::selectRaw('asset, status, COUNT(*) as count')
            ->groupBy('asset', 'status')
            ->get()
            ->groupBy('asset')
            ->map(fn ($items) => $items->pluck('count', 'status')->toArray())
            ->toArray();

        $scoreDistribution = [
            'high' => Lead::where('score', '>=', 70)->count(),
            'medium' => Lead::whereBetween('score', [40, 69])->count(),
            'low' => Lead::where('score', '<', 40)->count(),
        ];

        $revenueByAsset = NicheConfig::where('is_active', true)
            ->get(['domain', 'cpl'])
            ->map(function ($niche) {
                $sent = Lead::where('asset', $niche->domain)->where('status', 'sent')->count();

                return [
                    'domain' => $niche->domain,
                    'cpl' => (float) $niche->cpl,
                    'sent' => $sent,
                    'revenue' => round($sent * (float) $niche->cpl, 2),
                ];
            })
            ->all();

        return Inertia::render('Leads/Index', [
            'leads' => $leads,
            'filters' => $request->only(['status', 'asset', 'score_min']),
            'stats' => [
                'total' => Lead::count(),
                'today' => Lead::whereDate('created_at', today())->count(),
                'week' => Lead::where('created_at', '>=', now()->subDays(7))->count(),
                'avg_score' => round(Lead::avg('score') ?? 0, 1),
                'qualified' => Lead::where('status', 'qualified')->count(),
                'sent' => Lead::where('status', 'sent')->count(),
                'total_revenue' => collect($revenueByAsset)->sum('revenue'),
            ],
            'pipeline' => $pipeline,
            'byAsset' => $byAsset,
            'scoreDistribution' => $scoreDistribution,
            'revenueByAsset' => $revenueByAsset,
        ]);
    }
}
