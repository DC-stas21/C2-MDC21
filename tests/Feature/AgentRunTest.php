<?php

use App\Models\AgentRun;
use App\Models\User;

test('agent runs page returns correct inertia props', function () {
    $user = User::factory()->create();

    AgentRun::create([
        'agent_type' => 'orchestrator',
        'status' => 'completed',
        'started_at' => now()->subMinutes(5),
        'finished_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/agent-runs');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('AgentRuns/Index')
        ->has('runs')
        ->has('globalStats')
        ->has('agentProfiles', 8)
        ->has('dailyActivity')
        ->has('recentErrors')
    );
});

test('agent runs can be filtered by type', function () {
    $user = User::factory()->create();

    AgentRun::create(['agent_type' => 'orchestrator', 'status' => 'completed', 'started_at' => now(), 'finished_at' => now()]);
    AgentRun::create(['agent_type' => 'seo_content', 'status' => 'completed', 'started_at' => now(), 'finished_at' => now()]);

    $response = $this->actingAs($user)->get('/agent-runs?agent_type=orchestrator');

    $response->assertInertia(fn ($page) => $page
        ->has('runs.data', 1)
        ->where('filters.agent_type', 'orchestrator')
    );
});

test('agent runs can be filtered by status', function () {
    $user = User::factory()->create();

    AgentRun::create(['agent_type' => 'orchestrator', 'status' => 'completed', 'started_at' => now(), 'finished_at' => now()]);
    AgentRun::create(['agent_type' => 'orchestrator', 'status' => 'failed', 'started_at' => now(), 'finished_at' => now(), 'error' => 'Timeout']);

    $response = $this->actingAs($user)->get('/agent-runs?status=failed');

    $response->assertInertia(fn ($page) => $page
        ->has('runs.data', 1)
    );
});
