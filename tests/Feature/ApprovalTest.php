<?php

use App\Models\AgentRun;
use App\Models\Approval;
use App\Models\User;

test('can approve a pending approval', function () {
    $user = User::factory()->create();

    $run = AgentRun::create([
        'agent_type' => 'seo_content',
        'status' => 'completed',
        'started_at' => now()->subMinutes(5),
        'finished_at' => now(),
    ]);

    $approval = Approval::create([
        'agent_run_id' => $run->id,
        'action' => 'Publicar artículo test',
        'level' => 'N3',
        'status' => 'pending',
        'reason' => 'Requiere aprobación humana',
    ]);

    $this->actingAs($user)
        ->post("/approvals/{$approval->id}/approve", ['note' => 'Looks good'])
        ->assertRedirect();

    $approval->refresh();
    expect($approval->status)->toBe('approved');
    expect($approval->decided_by)->toBe($user->id);
    expect($approval->decision_note)->toBe('Looks good');
    expect($approval->decided_at)->not()->toBeNull();
});

test('can deny a pending approval', function () {
    $user = User::factory()->create();

    $run = AgentRun::create([
        'agent_type' => 'build_release',
        'status' => 'completed',
        'started_at' => now()->subMinutes(5),
        'finished_at' => now(),
    ]);

    $approval = Approval::create([
        'agent_run_id' => $run->id,
        'action' => 'Deploy a producción',
        'level' => 'N3',
        'status' => 'pending',
        'reason' => 'Requiere aprobación humana',
    ]);

    $this->actingAs($user)
        ->post("/approvals/{$approval->id}/deny", ['note' => 'Not ready yet'])
        ->assertRedirect();

    $approval->refresh();
    expect($approval->status)->toBe('denied');
    expect($approval->decision_note)->toBe('Not ready yet');
});

test('approval actions are logged in activity log', function () {
    $user = User::factory()->create();

    $run = AgentRun::create([
        'agent_type' => 'distribution',
        'status' => 'completed',
        'started_at' => now()->subMinutes(5),
        'finished_at' => now(),
    ]);

    $approval = Approval::create([
        'agent_run_id' => $run->id,
        'action' => 'Test action',
        'level' => 'N3',
        'status' => 'pending',
        'reason' => 'Test',
    ]);

    $this->actingAs($user)->post("/approvals/{$approval->id}/approve");

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => Approval::class,
        'subject_id' => $approval->id,
        'causer_id' => $user->id,
    ]);
});
