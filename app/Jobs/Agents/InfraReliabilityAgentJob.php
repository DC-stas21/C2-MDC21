<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;

class InfraReliabilityAgentJob extends BaseAgentJob
{
    public string $queue = 'agents-ops';

    public function __construct(
        private readonly string $checkType = 'full'
    ) {}

    protected function agentType(): string
    {
        return 'infra_reliability';
    }

    protected function input(): array
    {
        return ['check_type' => $this->checkType];
    }

    protected function execute(AgentRun $run): void
    {
        // TODO: Implementar Agente Infra & Reliability (script puro, sin IA)
        // Corre cada hora via Scheduler
        // 1. Monitorear uptime, SSL, espacio en disco y backups via Uptime Kuma API
        // 2. INFO → solo registrar en logs internos
        // 3. WARNING → notificar por Telegram + email con detalle
        // 4. CRITICAL → Telegram + email + bloquear nuevos deploys hasta resolución
        // 5. Humano confirma cierre → deploys se desbloquean automáticamente
        // NUNCA modifica DNS o Cloudflare de forma autónoma → solo propone al humano
    }
}
