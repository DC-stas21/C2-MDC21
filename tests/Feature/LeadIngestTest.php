<?php

use App\Models\NicheConfig;

test('lead ingest accepts valid data', function () {
    NicheConfig::create([
        'domain' => 'test-leads.es',
        'vertical' => 'Hipotecas',
        'is_active' => true,
        'build_status' => 'live',
        'config' => ['description' => 'Test'],
    ]);

    $this->postJson('/api/leads', [
        'asset' => 'test-leads.es',
        'data' => ['name' => 'Test User', 'email' => 'test@example.com', 'phone' => '+34612345678'],
    ])->assertStatus(201)
        ->assertJson(['status' => 'ok']);

    $this->assertDatabaseHas('leads', [
        'asset' => 'test-leads.es',
        'provider' => 'website_form',
        'status' => 'new',
    ]);
});

test('lead ingest rejects unknown asset', function () {
    $this->postJson('/api/leads', [
        'asset' => 'unknown-domain.es',
        'data' => ['name' => 'Test'],
    ])->assertStatus(422);
});

test('lead ingest validates required fields', function () {
    $this->postJson('/api/leads', [])
        ->assertStatus(422);
});
