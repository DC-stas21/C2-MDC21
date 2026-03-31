<?php

namespace App\Http\Controllers;

use App\Models\NicheConfig;
use Inertia\Inertia;
use Inertia\Response;

class NicheConfigController extends Controller
{
    public function index(): Response
    {
        $assets = NicheConfig::all()->map(function (NicheConfig $niche) {
            return [
                'id' => $niche->id,
                'domain' => $niche->domain,
                'vertical' => $niche->vertical,
                'is_active' => $niche->is_active,
                'build_status' => $niche->build_status,
                'build_metadata' => $niche->build_metadata,
                'description' => $niche->config['description'] ?? '',
                'audience' => $niche->config['target_audience'] ?? '',
                'tone' => $niche->config['tone'] ?? '',
                'created_at' => $niche->created_at?->toIso8601String(),
            ];
        });

        $stats = [
            'total' => NicheConfig::count(),
            'active' => NicheConfig::where('is_active', true)->count(),
            'live' => NicheConfig::where('build_status', NicheConfig::STATUS_LIVE)->count(),
            'building' => NicheConfig::where('build_status', NicheConfig::STATUS_BUILDING)->count(),
            'staging' => NicheConfig::where('build_status', NicheConfig::STATUS_STAGING)->count(),
            'failed' => NicheConfig::where('build_status', NicheConfig::STATUS_FAILED)->count(),
            'pending' => NicheConfig::where('build_status', NicheConfig::STATUS_PENDING)->count(),
        ];

        return Inertia::render('NicheConfigs/Index', [
            'assets' => $assets,
            'stats' => $stats,
        ]);
    }
}
