<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;

class DistributionAgentJob extends BaseAgentJob
{
    public string $queue = 'agents';

    public function __construct(
        private readonly string $nicheConfigId,
        private readonly string $channel,
        private readonly ?string $blogPostId = null
    ) {}

    protected function agentType(): string
    {
        return 'distribution';
    }

    protected function input(): array
    {
        return [
            'niche_config_id' => $this->nicheConfigId,
            'channel' => $this->channel,
            'blog_post_id' => $this->blogPostId,
        ];
    }

    protected function execute(AgentRun $run): void
    {
        // TODO: Implementar Agente Distribución
        // 1. Investigar comunidades relevantes y trending topics del nicho
        // 2. Generar borrador listo para copiar + dónde + por qué ahora + riesgo reputacional
        // 3. Policy valida tono y claims
        // 4. Enviar al publicador humano por Telegram/email
        // 5. Registrar en editorial_calendar estado 'pending_human'
        // NUNCA publica automáticamente — siempre N3
    }
}
