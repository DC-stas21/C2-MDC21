<?php

use App\Models\User;

test('guest is redirected to login', function () {
    $this->get('/')->assertRedirect('/admin/login');
    $this->get('/agent-runs')->assertRedirect('/admin/login');
    $this->get('/approvals')->assertRedirect('/admin/login');
    $this->get('/assets')->assertRedirect('/admin/login');
    $this->get('/content')->assertRedirect('/admin/login');
    $this->get('/leads')->assertRedirect('/admin/login');
});

test('dashboard loads for authenticated user', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/')->assertStatus(200);
});

test('agent runs page loads', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/agent-runs')->assertStatus(200);
});

test('approvals page loads', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/approvals')->assertStatus(200);
});

test('assets page loads', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/assets')->assertStatus(200);
});

test('content page loads', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/content')->assertStatus(200);
});

test('leads page loads', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/leads')->assertStatus(200);
});
