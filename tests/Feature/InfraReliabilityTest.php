<?php

use App\Jobs\Agents\InfraReliabilityAgentJob;
use App\Models\AgentRun;
use Illuminate\Support\Facades\Cache;

test('infra reliability job runs and records results', function () {
    $job = new InfraReliabilityAgentJob('full');
    $job->handle();

    $run = AgentRun::where('agent_type', 'infra_reliability')->latest()->first();

    expect($run)->not()->toBeNull();
    expect($run->status)->toBe('completed');
    expect($run->output)->toHaveKey('severity');
    expect($run->output)->toHaveKey('checks');
    expect($run->output)->toHaveKey('alerts_count');
    expect($run->output)->toHaveKey('checked_at');
    expect($run->output['checks'])->toHaveKey('database');
    expect($run->output['checks'])->toHaveKey('redis');
    expect($run->output['checks'])->toHaveKey('disk');
});

test('infra reliability caches latest status', function () {
    Cache::forget('infra:latest_status');

    $job = new InfraReliabilityAgentJob('quick');
    $job->handle();

    $cached = Cache::get('infra:latest_status');

    expect($cached)->not()->toBeNull();
    expect($cached)->toHaveKey('severity');
    expect($cached)->toHaveKey('alerts_count');
    expect($cached)->toHaveKey('checked_at');
});

test('database check returns healthy status', function () {
    $job = new InfraReliabilityAgentJob;
    $job->handle();

    $run = AgentRun::where('agent_type', 'infra_reliability')->latest()->first();
    $dbCheck = $run->output['checks']['database'];

    expect($dbCheck['status'])->toBeIn(['ok', 'warning']);
    expect($dbCheck)->toHaveKey('latency_ms');
    expect($dbCheck)->toHaveKey('database_size');
});

test('redis check returns healthy status', function () {
    $job = new InfraReliabilityAgentJob;
    $job->handle();

    $run = AgentRun::where('agent_type', 'infra_reliability')->latest()->first();
    $redisCheck = $run->output['checks']['redis'];

    expect($redisCheck['status'])->toBeIn(['ok', 'warning']);
    expect($redisCheck)->toHaveKey('latency_ms');
    expect($redisCheck)->toHaveKey('memory_used');
});

test('disk check returns status with usage info', function () {
    $job = new InfraReliabilityAgentJob;
    $job->handle();

    $run = AgentRun::where('agent_type', 'infra_reliability')->latest()->first();
    $diskCheck = $run->output['checks']['disk'];

    expect($diskCheck)->toHaveKey('used_pct');
    expect($diskCheck)->toHaveKey('free_gb');
    expect($diskCheck['used_pct'])->toBeGreaterThan(0);
});
