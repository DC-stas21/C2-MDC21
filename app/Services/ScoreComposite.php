<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ScoreComposite
{
    private const CACHE_TTL = 3600;

    private const WEIGHTS = [
        'traffic' => 0.25,
        'engagement' => 0.20,
        'revenue' => 0.20,
        'quality' => 0.15,
        'trend' => 0.10,
        'core_web_vitals' => 0.10,
    ];

    public function calculate(string $nicheConfigId, array $metrics): float
    {
        $score = 0.0;

        foreach (self::WEIGHTS as $dimension => $weight) {
            $value = $this->normalizeDimension($dimension, $metrics[$dimension] ?? null);
            $score += $value * $weight;
        }

        $score = round(min(100, max(0, $score * 100)), 2);

        Cache::put("score_composite:{$nicheConfigId}", $score, self::CACHE_TTL);

        return $score;
    }

    public function getLatest(string $nicheConfigId): ?float
    {
        return Cache::get("score_composite:{$nicheConfigId}");
    }

    public function classify(float $score): string
    {
        return match (true) {
            $score >= 80 => 'excellent',
            $score >= 60 => 'good',
            $score >= 40 => 'average',
            $score >= 20 => 'poor',
            default => 'critical',
        };
    }

    public function detectAlerts(string $nicheConfigId, array $metrics): array
    {
        $alerts = [];

        if (($metrics['core_web_vitals']['lcp'] ?? 0) > 4000) {
            $alerts[] = ['type' => 'performance', 'severity' => 'high', 'message' => 'LCP > 4s'];
        }

        if (($metrics['core_web_vitals']['performance'] ?? 100) < 60) {
            $alerts[] = ['type' => 'performance', 'severity' => 'high', 'message' => 'Performance score < 60'];
        }

        if (($metrics['traffic']['sessions'] ?? 0) < ($metrics['traffic']['baseline'] ?? 0) * 0.5) {
            $alerts[] = ['type' => 'traffic', 'severity' => 'critical', 'message' => 'Traffic drop > 50%'];
        }

        return $alerts;
    }

    private function normalizeDimension(string $dimension, mixed $value): float
    {
        if ($value === null) {
            return 0.5;
        }

        return match ($dimension) {
            'traffic' => min(1.0, ($value['sessions'] ?? 0) / max(1, $value['baseline'] ?? 1000)),
            'engagement' => min(1.0, ($value['avg_time'] ?? 0) / 300),
            'revenue' => min(1.0, ($value['amount'] ?? 0) / max(1, $value['target'] ?? 1000)),
            'quality' => ($value['score'] ?? 50) / 100,
            'trend' => min(1.0, max(0.0, (($value['change_pct'] ?? 0) + 50) / 100)),
            'core_web_vitals' => $this->normalizeWebVitals($value),
            default => 0.5,
        };
    }

    private function normalizeWebVitals(array $vitals): float
    {
        $performance = ($vitals['performance'] ?? 60) / 100;
        $lcp = $vitals['lcp'] ?? 2500;
        $lcpScore = $lcp <= 2500 ? 1.0 : ($lcp <= 4000 ? 0.5 : 0.0);

        return ($performance + $lcpScore) / 2;
    }
}
