<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;

class QAExperimentationAgentJob extends BaseAgentJob
{
    public string $queue = 'agents-ops';

    public int $timeout = 600;

    public function __construct(
        private readonly string $nicheConfigId,
        private readonly string $task = 'qa',
        private readonly ?string $experimentId = null
    ) {}

    protected function agentType(): string
    {
        return 'qa_experimentation';
    }

    protected function input(): array
    {
        return [
            'niche_config_id' => $this->nicheConfigId,
            'task' => $this->task,
            'experiment_id' => $this->experimentId,
        ];
    }

    protected function execute(AgentRun $run): void
    {
        // TODO: Implementar Agente QA & Experimentación (script puro, sin IA)
        // Corre automáticamente tras cada build en staging
        // 'qa':
        //   1. Playwright valida links, formularios y renders
        //   2. Lighthouse CI comprueba Performance < 60 o LCP > 4s → bloquea deploy
        //   3. Pest PHP corre todos los tests unitarios
        //   4. Si pasa todo → QA passed → notifica a BuildReleaseAgentJob
        //   5. Si falla → bloquea deploy + notifica humano con reporte + screenshot
        //   6. Override humano queda en audit trail con nombre del aprobador
        // 'evaluate_ab':
        //   - Evalúa ganador cuando hay significancia estadística
        //   - Notifica humano → confirmación antes de aplicar en producción via Pennant
    }
}
