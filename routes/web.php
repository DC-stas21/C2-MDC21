<?php

use App\Http\Controllers\AgentRunController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NicheConfigController;
use Illuminate\Support\Facades\Route;

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// App (auth required)
Route::middleware(['auth'])->group(function () {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/agent-runs', [AgentRunController::class, 'index'])->name('agent-runs.index');
    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::post('/approvals/{approval}/approve', [ApprovalController::class, 'approve'])->name('approvals.approve');
    Route::post('/approvals/{approval}/deny', [ApprovalController::class, 'deny'])->name('approvals.deny');
    Route::get('/assets', [NicheConfigController::class, 'index'])->name('assets.index');
});
