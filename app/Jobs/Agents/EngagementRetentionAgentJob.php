<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;

class EngagementRetentionAgentJob extends BaseAgentJob
{
    public string $queue = 'agents';

    public function __construct(
        private readonly string $nicheConfigId,
        private readonly string $task = 'newsletter'
    ) {}

    protected function agentType(): string
    {
        return 'engagement_retention';
    }

    protected function input(): array
    {
        return [
            'niche_config_id' => $this->nicheConfigId,
            'task' => $this->task,
        ];
    }

    protected function execute(AgentRun $run): void
    {
        // TODO: Implementar Agente Engagement & Retención
        // Tareas disponibles via $this->task:
        // 'newsletter': redactar newsletter completa → Policy valida → enviar al publicador humano
        // 'faq': generar FAQ estática con Batch API (una sola vez por nicho)
        // 'drip': gestionar drip emails automáticos vía Resend (solo tras activación humana)
        // 'pdf': generar PDFs descargables con Spatie Laravel PDF
        // 'ab_test': gestionar A/B tests con Pennant → ganador requiere confirmación humana
    }
}
