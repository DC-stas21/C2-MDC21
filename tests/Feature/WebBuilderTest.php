<?php

use App\Models\NicheConfig;
use App\Services\WebConfigTemplateService;

test('template service generates valid config for hipotecas', function () {
    $niche = NicheConfig::create([
        'domain' => 'test-hipotecas.es',
        'vertical' => 'Hipotecas',
        'is_active' => true,
        'config' => [
            'description' => 'Calculadora de hipotecas online',
            'target_audience' => 'Compradores de vivienda',
            'tone' => 'profesional y cercano',
            'keywords' => 'hipotecas, simulador',
        ],
    ]);

    $service = new WebConfigTemplateService;
    $config = $service->generateForNiche($niche);

    expect($config)->toHaveKey('meta');
    expect($config)->toHaveKey('design');
    expect($config)->toHaveKey('navigation');
    expect($config)->toHaveKey('pages');
    expect($config)->toHaveKey('footer');
    expect($config['meta']['domain'])->toBe('test-hipotecas.es');
    expect($config['meta']['vertical'])->toBe('Hipotecas');
    expect(count($config['pages']))->toBeGreaterThanOrEqual(4);
});

test('template service generates correct tool for each vertical', function () {
    $verticals = ['Hipotecas', 'Energía', 'Préstamos', 'Solar'];

    foreach ($verticals as $vertical) {
        $niche = NicheConfig::create([
            'domain' => 'test-'.strtolower($vertical).'.es',
            'vertical' => $vertical,
            'is_active' => true,
            'config' => ['description' => "Test {$vertical}"],
        ]);

        $service = new WebConfigTemplateService;
        $config = $service->generateForNiche($niche);

        $toolPage = collect($config['pages'])->firstWhere('type', 'tool');
        expect($toolPage)->not()->toBeNull("Tool page missing for {$vertical}");
        expect($toolPage['sections'][1]['tool_config'])->toHaveKey('fields');
        expect($toolPage['sections'][1]['tool_config'])->toHaveKey('formula');
    }
});

test('template service generates legal pages', function () {
    $niche = NicheConfig::create([
        'domain' => 'test-legal.es',
        'vertical' => 'Seguros',
        'is_active' => true,
        'config' => ['description' => 'Test'],
    ]);

    $service = new WebConfigTemplateService;
    $config = $service->generateForNiche($niche);

    $slugs = collect($config['pages'])->pluck('slug')->toArray();
    expect($slugs)->toContain('/aviso-legal');
    expect($slugs)->toContain('/privacidad');
    expect($slugs)->toContain('/cookies');
});

test('template service uses custom colors when provided', function () {
    $niche = NicheConfig::create([
        'domain' => 'test-colors.es',
        'vertical' => 'Hipotecas',
        'is_active' => true,
        'colors' => ['primary' => '#ff0000', 'secondary' => '#00ff00'],
        'config' => ['description' => 'Test'],
    ]);

    $service = new WebConfigTemplateService;
    $config = $service->generateForNiche($niche);

    expect($config['design']['colors']['primary'])->toBe('#ff0000');
    expect($config['design']['colors']['secondary'])->toBe('#00ff00');
});

test('niche config has build status', function () {
    $niche = NicheConfig::create([
        'domain' => 'test-status.es',
        'vertical' => 'Solar',
        'is_active' => true,
        'build_status' => 'pending',
        'config' => ['description' => 'Test'],
    ]);

    expect($niche->build_status)->toBe('pending');
    expect($niche->isPending())->toBeTrue();

    $niche->update(['build_status' => 'live']);
    expect($niche->isLive())->toBeTrue();
    expect($niche->siteUrl())->toBe('https://test-status.es');
});
