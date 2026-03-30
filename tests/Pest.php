<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

uses()->beforeEach(function () {
    $this->withoutVite();

    // Disable Inertia page component file existence check (no Node build in CI)
    config()->set('inertia.testing.ensure_pages_exist', false);
})->in('Feature');
