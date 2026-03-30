<?php

use App\Models\NicheConfig;
use App\Models\Policy;
use App\Models\PromptVersion;
use App\Services\AssetSetupService;

test('asset setup creates global policies if none exist', function () {
    $asset = NicheConfig::create([
        'domain' => 'test-setup.es',
        'vertical' => 'Hipotecas',
        'is_active' => true,
        'config' => [
            'description' => 'Calculadora de hipotecas',
            'target_audience' => 'Compradores de vivienda',
            'tone' => 'profesional y cercano',
        ],
    ]);

    $service = new AssetSetupService;
    $report = $service->setup($asset);

    // Should create 5 global policies + 1 asset-specific
    expect(Policy::where('scope', 'global')->count())->toBe(5);
    expect(Policy::where('scope', 'test-setup.es')->count())->toBe(1);
    expect($report['policies_created'])->toBe(1);
});

test('asset setup creates prompts for agents', function () {
    $asset = NicheConfig::create([
        'domain' => 'test-prompts.es',
        'vertical' => 'Energía',
        'is_active' => true,
        'config' => [
            'description' => 'Comparador de tarifas eléctricas',
            'target_audience' => 'Hogares españoles',
            'tone' => 'educativo y empático',
            'keywords' => 'tarifas luz, comparador energía',
        ],
    ]);

    $service = new AssetSetupService;
    $report = $service->setup($asset);

    expect($report['prompts_created'])->toBeGreaterThan(0);
    expect(PromptVersion::where('is_active', true)->count())->toBeGreaterThan(0);

    // Check prompts contain asset context
    $seoPrompt = PromptVersion::where('agent_type', 'seo_content')->where('is_active', true)->first();
    expect($seoPrompt)->not()->toBeNull();
    expect($seoPrompt->prompt_text)->toContain('test-prompts.es');
    expect($seoPrompt->prompt_text)->toContain('Energía');
});

test('asset setup does not duplicate policies on second run', function () {
    $asset = NicheConfig::create([
        'domain' => 'test-nodupe.es',
        'vertical' => 'Seguros',
        'is_active' => true,
        'config' => ['description' => 'Comparador de seguros'],
    ]);

    $service = new AssetSetupService;
    $service->setup($asset);
    $report = $service->setup($asset);

    // Second run should not create duplicates
    expect($report['policies_created'])->toBe(0);
    expect($report['prompts_created'])->toBe(0);
    expect(Policy::where('scope', 'test-nodupe.es')->count())->toBe(1);
});

test('asset setup uses vertical-specific rules', function () {
    $asset = NicheConfig::create([
        'domain' => 'test-vertical.es',
        'vertical' => 'Préstamos',
        'is_active' => true,
        'config' => ['description' => 'Comparador de préstamos'],
    ]);

    $service = new AssetSetupService;
    $service->setup($asset);

    $policy = Policy::where('scope', 'test-vertical.es')->first();
    expect($policy->content)->toContain('TAE');
});
