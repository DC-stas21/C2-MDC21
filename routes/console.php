<?php

use App\Jobs\Agents\InfraReliabilityAgentJob;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Agent Scheduler
|--------------------------------------------------------------------------
| Todos los agentes se ejecutan como Jobs en cola.
| El Scheduler los despacha según su frecuencia definida.
|
| Capa 0 — Núcleo
|   OrchestratorAgentJob: cada 12h (pendiente API keys)
|
| Capa 2 — Operativos
|   InfraReliabilityAgentJob: cada hora
|
*/

// Infra & Reliability — check completo cada hora
Schedule::job(new InfraReliabilityAgentJob('full'))
    ->hourly()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('infra-reliability-hourly');

// Infra & Reliability — check rápido cada 15 minutos (solo DB + Redis, sin HTTP a activos)
Schedule::job(new InfraReliabilityAgentJob('quick'))
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->onOneServer()
    ->name('infra-reliability-quick');
