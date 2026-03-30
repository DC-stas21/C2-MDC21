<?php

use App\Models\AgentRun;
use App\Models\Approval;
use App\Models\User;

test('dashboard returns correct inertia props', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->has('stats')
        ->has('agentStatuses')
        ->has('pendingApprovals')
        ->has('agentActivity')
        ->has('assets')
        ->has('timeline')
    );
});

test('dashboard stats reflect real data', function () {
    $user = User::factory()->create();

    AgentRun::create([
        'agent_type' => 'orchestrator',
        'status' => 'running',
        'started_at' => now(),
    ]);

    AgentRun::create([
        'agent_type' => 'seo_content',
        'status' => 'completed',
        'started_at' => now()->subMinutes(5),
        'finished_at' => now(),
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertInertia(fn ($page) => $page
        ->where('stats.agents_active', 1)
        ->where('stats.agents_completed_today', 1)
    );
});

test('pending approvals appear in dashboard', function () {
    $user = User::factory()->create();

    $run = AgentRun::create([
        'agent_type' => 'distribution',
        'status' => 'completed',
        'started_at' => now()->subMinutes(5),
        'finished_at' => now(),
    ]);

    Approval::create([
        'agent_run_id' => $run->id,
        'action' => 'Publicar artículo test',
        'level' => 'N3',
        'status' => 'pending',
        'reason' => 'Test reason',
    ]);

    $response = $this->actingAs($user)->get('/');

    $response->assertInertia(fn ($page) => $page
        ->where('stats.approvals_pending', 1)
        ->has('pendingApprovals', 1)
    );
});
