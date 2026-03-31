<?php

use App\Jobs\Agents\PolicyBrandAgentJob;
use App\Jobs\Agents\QAExperimentationAgentJob;
use App\Models\AgentRun;
use App\Models\NicheConfig;
use App\Models\Policy;
use App\Services\WebConfigTemplateService;
use Illuminate\Support\Facades\Queue;

test('policy brand chains to QA when approving site_config', function () {
    Queue::fake([QAExperimentationAgentJob::class]);

    Policy::create(['scope' => 'global', 'type' => 'tone', 'content' => 'Test rule', 'is_active' => true]);

    $niche = NicheConfig::create([
        'domain' => 'test-chain.es',
        'vertical' => 'Hipotecas',
        'is_active' => true,
        'build_status' => 'building',
        'config' => ['description' => 'Test'],
    ]);

    $parentRun = AgentRun::create([
        'agent_type' => 'web_builder',
        'status' => 'completed',
        'started_at' => now(),
        'finished_at' => now(),
    ]);

    $job = new PolicyBrandAgentJob(
        content: '{"meta":{"domain":"test-chain.es"},"pages":[]}',
        contentType: 'site_config',
        requestingAgentRunId: $parentRun->id,
        assetDomain: 'test-chain.es'
    );
    $job->handle();

    // Should have dispatched QA with qa_web task
    Queue::assertPushed(QAExperimentationAgentJob::class);
});

test('policy brand does NOT chain to QA for non-site_config content', function () {
    Queue::fake([QAExperimentationAgentJob::class]);

    $parentRun = AgentRun::create([
        'agent_type' => 'seo_content',
        'status' => 'completed',
        'started_at' => now(),
        'finished_at' => now(),
    ]);

    $job = new PolicyBrandAgentJob(
        content: 'Un artículo sobre hipotecas en España.',
        contentType: 'article',
        requestingAgentRunId: $parentRun->id
    );
    $job->handle();

    Queue::assertNotPushed(QAExperimentationAgentJob::class);
});

test('template service generates valid config for ciberseguridad', function () {
    $niche = NicheConfig::create([
        'domain' => 'test-ciber.es',
        'vertical' => 'Ciberseguridad',
        'is_active' => true,
        'config' => ['description' => 'Audit de seguridad para PYMEs'],
    ]);

    $service = new WebConfigTemplateService;
    $config = $service->generateForNiche($niche);

    $toolPage = collect($config['pages'])->firstWhere('type', 'tool');
    expect($toolPage)->not()->toBeNull();
    expect($toolPage['sections'][1]['tool_config']['checker_type'])->toBe('cybersecurity_audit');
    expect($toolPage['sections'][1]['tool_config']['checks'])->toHaveCount(9);
});

test('template service generates valid config for contabilidad', function () {
    $niche = NicheConfig::create([
        'domain' => 'test-conta.es',
        'vertical' => 'Contabilidad',
        'is_active' => true,
        'config' => ['description' => 'Calculadora para autónomos'],
    ]);

    $service = new WebConfigTemplateService;
    $config = $service->generateForNiche($niche);

    $toolPage = collect($config['pages'])->firstWhere('type', 'tool');
    expect($toolPage)->not()->toBeNull();
    expect($toolPage['sections'][1]['tool_config']['formula'])->toBe('spanish_autonomo');
});
