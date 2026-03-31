<?php

use App\Http\Controllers\Api\LeadIngestController;
use Illuminate\Support\Facades\Route;

// Public API — receives data from generated websites
Route::post('/leads', LeadIngestController::class)->name('api.leads.ingest');
