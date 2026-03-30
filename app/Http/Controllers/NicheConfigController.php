<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\NicheConfig;
use App\Services\ScoreComposite;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class NicheConfigController extends Controller
{
    public function index(ScoreComposite $scoreComposite): Response
    {
        $assets = NicheConfig::all()->map(function (NicheConfig $niche) use ($scoreComposite) {
            $score = Cache::get("score_composite:{$niche->id}");
            $postsCount = BlogPost::where('asset', $niche->domain)->count();
            $postsPublished = BlogPost::where('asset', $niche->domain)->where('status', 'published')->count();

            return [
                'id' => $niche->id,
                'domain' => $niche->domain,
                'vertical' => $niche->vertical,
                'cpl' => $niche->cpl,
                'is_active' => $niche->is_active,
                'score' => $score,
                'classification' => $score !== null ? $scoreComposite->classify($score) : null,
                'config' => $niche->config,
                'colors' => $niche->colors,
                'posts_total' => $postsCount,
                'posts_published' => $postsPublished,
                'created_at' => $niche->created_at?->toIso8601String(),
            ];
        });

        return Inertia::render('NicheConfigs/Index', [
            'assets' => $assets,
            'stats' => [
                'total' => NicheConfig::count(),
                'active' => NicheConfig::where('is_active', true)->count(),
                'inactive' => NicheConfig::where('is_active', false)->count(),
                'total_posts' => BlogPost::count(),
                'avg_cpl' => NicheConfig::where('is_active', true)->avg('cpl'),
            ],
            'scoreDistribution' => $this->getScoreDistribution($scoreComposite),
        ]);
    }

    private function getScoreDistribution(ScoreComposite $scoreComposite): array
    {
        $buckets = ['excellent' => 0, 'good' => 0, 'average' => 0, 'poor' => 0, 'critical' => 0, 'no_data' => 0];

        NicheConfig::where('is_active', true)->pluck('id')->each(function (string $id) use (&$buckets, $scoreComposite) {
            $score = Cache::get("score_composite:{$id}");
            if ($score === null) {
                $buckets['no_data']++;
            } else {
                $buckets[$scoreComposite->classify($score)]++;
            }
        });

        return $buckets;
    }
}
