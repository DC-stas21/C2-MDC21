<?php

use App\Jobs\Agents\PolicyBrandAgentJob;
use App\Models\AgentRun;
use App\Models\Approval;
use App\Models\Policy;

test('policy brand approves clean content', function () {
    Policy::create([
        'scope' => 'global',
        'type' => 'tone',
        'content' => 'No usar superlativos',
        'is_active' => true,
    ]);

    $parentRun = AgentRun::create([
        'agent_type' => 'seo_content',
        'status' => 'completed',
        'started_at' => now(),
        'finished_at' => now(),
    ]);

    $job = new PolicyBrandAgentJob(
        content: 'Esta guía te ayuda a entender cómo funcionan las hipotecas en España. Analizamos los tipos actuales del mercado.',
        contentType: 'article',
        requestingAgentRunId: $parentRun->id,
        assetDomain: 'calculahipoteca.es'
    );
    $job->handle();

    $run = AgentRun::where('agent_type', 'policy_brand')->latest()->first();

    expect($run->status)->toBe('completed');
    expect($run->output['decision'])->toBe('approved');
    expect($run->output['method'])->toBe('rule_based'); // No API key in tests
});

test('policy brand rejects content with forbidden words', function () {
    Policy::create([
        'scope' => 'global',
        'type' => 'tone',
        'content' => 'No usar superlativos',
        'is_active' => true,
    ]);

    $parentRun = AgentRun::create([
        'agent_type' => 'seo_content',
        'status' => 'completed',
        'started_at' => now(),
        'finished_at' => now(),
    ]);

    $job = new PolicyBrandAgentJob(
        content: 'Somos el mejor comparador de hipotecas. Te garantizamos el precio más bajo del mercado.',
        contentType: 'article',
        requestingAgentRunId: $parentRun->id
    );
    $job->handle();

    $run = AgentRun::where('agent_type', 'policy_brand')->latest()->first();

    expect($run->status)->toBe('completed');
    expect($run->output['decision'])->toBe('rejected');
    expect($run->output['violations'])->not()->toBeEmpty();
});

test('policy brand creates N3 approval when content is rejected', function () {
    Policy::create([
        'scope' => 'global',
        'type' => 'brand',
        'content' => 'No mencionar competidores',
        'is_active' => true,
    ]);

    $parentRun = AgentRun::create([
        'agent_type' => 'distribution',
        'status' => 'completed',
        'started_at' => now(),
        'finished_at' => now(),
    ]);

    $job = new PolicyBrandAgentJob(
        content: 'Nuestra calculadora es mejor que la de BBVA y Santander.',
        contentType: 'social_post',
        requestingAgentRunId: $parentRun->id,
        assetDomain: 'calculahipoteca.es'
    );
    $job->handle();

    // Should have created an N3 approval for human review
    $approval = Approval::where('level', 'N3')->latest()->first();

    expect($approval)->not()->toBeNull();
    expect($approval->status)->toBe('pending');
    expect($approval->action)->toContain('Policy rechazó');
});

test('policy brand loads asset-specific policies', function () {
    Policy::create(['scope' => 'global', 'type' => 'tone', 'content' => 'Global rule', 'is_active' => true]);
    Policy::create(['scope' => 'calculahipoteca.es', 'type' => 'content', 'content' => 'Asset rule', 'is_active' => true]);
    Policy::create(['scope' => 'segurobarato.net', 'type' => 'content', 'content' => 'Other asset rule', 'is_active' => true]);

    $parentRun = AgentRun::create([
        'agent_type' => 'seo_content',
        'status' => 'completed',
        'started_at' => now(),
        'finished_at' => now(),
    ]);

    $job = new PolicyBrandAgentJob(
        content: 'Contenido limpio sin problemas.',
        contentType: 'article',
        requestingAgentRunId: $parentRun->id,
        assetDomain: 'calculahipoteca.es'
    );
    $job->handle();

    $run = AgentRun::where('agent_type', 'policy_brand')->latest()->first();

    // Should have checked global + calculahipoteca.es but NOT segurobarato.net
    expect($run->output['policies_checked'])->toBe(2);
});

test('policy brand auto-approves when no policies exist', function () {
    // No policies in DB

    $parentRun = AgentRun::create([
        'agent_type' => 'seo_content',
        'status' => 'completed',
        'started_at' => now(),
        'finished_at' => now(),
    ]);

    $job = new PolicyBrandAgentJob(
        content: 'Cualquier contenido.',
        contentType: 'article',
        requestingAgentRunId: $parentRun->id
    );
    $job->handle();

    $run = AgentRun::where('agent_type', 'policy_brand')->latest()->first();

    expect($run->output['decision'])->toBe('approved');
    expect($run->output['method'])->toBe('no_policies');
});

test('policy brand detects privacy violations', function () {
    Policy::create([
        'scope' => 'global',
        'type' => 'privacy',
        'content' => 'No exponer datos personales',
        'is_active' => true,
    ]);

    $parentRun = AgentRun::create([
        'agent_type' => 'engagement_retention',
        'status' => 'completed',
        'started_at' => now(),
        'finished_at' => now(),
    ]);

    $job = new PolicyBrandAgentJob(
        content: 'El cliente Juan contactó desde juan.perez@gmail.com para pedir información.',
        contentType: 'newsletter',
        requestingAgentRunId: $parentRun->id
    );
    $job->handle();

    $run = AgentRun::where('agent_type', 'policy_brand')->latest()->first();

    expect($run->output['decision'])->toBe('rejected');
    $violations = collect($run->output['violations']);
    expect($violations->pluck('violation')->implode(' '))->toContain('Email personal');
});
