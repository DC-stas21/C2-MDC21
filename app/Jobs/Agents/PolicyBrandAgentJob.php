<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;
use App\Models\Policy;

class PolicyBrandAgentJob extends BaseAgentJob
{
    public string $queue = 'agents';

    public function __construct(
        private readonly string $content,
        private readonly string $contentType,
        private readonly string $requestingAgentRunId,
        private readonly ?string $assetId = null
    ) {}

    protected function agentType(): string
    {
        return 'policy_brand';
    }

    protected function input(): array
    {
        return [
            'content' => $this->content,
            'content_type' => $this->contentType,
            'requesting_agent_run_id' => $this->requestingAgentRunId,
            'asset_id' => $this->assetId,
        ];
    }

    protected function execute(AgentRun $run): void
    {
        // TODO: Implementar validación Policy & Brand
        // 1. Cargar reglas activas de tabla policies filtradas por asset_id
        // 2. Llamar Claude Haiku con el contenido + reglas
        // 3. Evaluar approved/rejected + razón detallada
        // 4. Si rejected: notificar al humano por Telegram/email
        // 5. Registrar resultado en output
    }
}
