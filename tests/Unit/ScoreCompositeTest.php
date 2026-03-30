<?php

use App\Services\ScoreComposite;

test('calculates score with all dimensions', function () {
    $service = new ScoreComposite;

    $score = $service->calculate('test-niche-id', [
        'traffic' => ['sessions' => 800, 'baseline' => 1000],
        'engagement' => ['avg_time' => 180],
        'revenue' => ['amount' => 700, 'target' => 1000],
        'quality' => ['score' => 75],
        'trend' => ['change_pct' => 10],
        'core_web_vitals' => ['performance' => 85, 'lcp' => 2000],
    ]);

    expect($score)->toBeGreaterThan(0)->toBeLessThanOrEqual(100);
});

test('classifies scores correctly', function () {
    $service = new ScoreComposite;

    expect($service->classify(90))->toBe('excellent');
    expect($service->classify(75))->toBe('good');
    expect($service->classify(50))->toBe('average');
    expect($service->classify(30))->toBe('poor');
    expect($service->classify(10))->toBe('critical');
});

test('detects LCP alert', function () {
    $service = new ScoreComposite;

    $alerts = $service->detectAlerts('test-id', [
        'core_web_vitals' => ['lcp' => 5000, 'performance' => 80],
    ]);

    expect($alerts)->toHaveCount(1);
    expect($alerts[0]['type'])->toBe('performance');
    expect($alerts[0]['message'])->toBe('LCP > 4s');
});

test('detects performance alert', function () {
    $service = new ScoreComposite;

    $alerts = $service->detectAlerts('test-id', [
        'core_web_vitals' => ['performance' => 45, 'lcp' => 2000],
    ]);

    expect($alerts)->toHaveCount(1);
    expect($alerts[0]['message'])->toBe('Performance score < 60');
});

test('detects traffic drop alert', function () {
    $service = new ScoreComposite;

    $alerts = $service->detectAlerts('test-id', [
        'traffic' => ['sessions' => 200, 'baseline' => 1000],
    ]);

    expect($alerts)->toHaveCount(1);
    expect($alerts[0]['severity'])->toBe('critical');
});

test('returns no alerts when everything is healthy', function () {
    $service = new ScoreComposite;

    $alerts = $service->detectAlerts('test-id', [
        'core_web_vitals' => ['lcp' => 2000, 'performance' => 90],
        'traffic' => ['sessions' => 900, 'baseline' => 1000],
    ]);

    expect($alerts)->toBeEmpty();
});
