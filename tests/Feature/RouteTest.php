<?php

use App\Models\User;

test('guest is redirected to login', function () {
    $this->get('/')->assertRedirect('/login');
    $this->get('/agent-runs')->assertRedirect('/login');
    $this->get('/approvals')->assertRedirect('/login');
    $this->get('/assets')->assertRedirect('/login');
});

test('login page loads', function () {
    $this->get('/login')->assertStatus(200);
});

test('user can login', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect('/');
});

test('invalid credentials are rejected', function () {
    $this->post('/login', ['email' => 'fake@test.com', 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
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
