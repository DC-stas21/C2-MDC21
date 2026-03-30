<?php

use App\Jobs\Agents\InfraReliabilityAgentJob;
use App\Jobs\Agents\OrchestratorAgentJob;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Agent Scheduler — C2 MDC21
|--------------------------------------------------------------------------
|
| Capa 0 — Núcleo
|   Orchestrator:       cada 12h (05:00 y 17:00)
|
| Capa 2 — Operativos
|   InfraReliability:   full cada hora, quick cada 15min
|
| Capa 1 — Funcionales (SEO, Distribution, Engagement, Monetization)
|   Se disparan bajo demanda o encadenados por el Orchestrator.
|   No se programan en el scheduler porque dependen del contexto
|   (qué activo, qué canal, qué tarea).
|
| Capa 2 — Build & Release, QA
|   Se disparan automáticamente tras deploys o por el Orchestrator.
|
*/

// === CAPA 0: Orquestador — ciclo 12h ===
Schedule::job(new OrchestratorAgentJob)
    ->twiceDaily(5, 17)
    ->withoutOverlapping()
    ->onOneServer()
    ->name('orchestrator-12h');

// === CAPA 2: Infra & Reliability ===
Schedule::job(new InfraReliabilityAgentJob('full'))
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('infra-reliability-hourly');

Schedule::job(new InfraReliabilityAgentJob('quick'))
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('infra-reliability-quick');
