<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;

class MonetizationLeadsAgentJob extends BaseAgentJob
{
    public string $queue = 'agents';

    public function __construct(
        private readonly string $nicheConfigId,
        private readonly string $task = 'score_leads'
    ) {}

    protected function agentType(): string
    {
        return 'monetization_leads';
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
        // TODO: Implementar Agente Monetización & Leads
        // 'score_leads':
        //   - Score > 70 → envío automático al proveedor (N1)
        //   - Score 40-70 → revisión humana (N3)
        //   - Score < 40 → descarte automático (N1)
        // 'find_providers': gpt-4o identifica proveedores potenciales por nicho
        // 'generate_proposal': Claude genera propuesta CPL → Policy valida → humano aprueba y envía
        // NUNCA envía propuesta comercial sin aprobación humana
    }
}
