<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;
use App\Services\ScoreComposite;

class OrchestratorAgentJob extends BaseAgentJob
{
    public string $queue = 'agents';

    public int $timeout = 600;

    public function __construct(
        private readonly array $assetIds = []
    ) {}

    protected function agentType(): string
    {
        return 'orchestrator';
    }

    protected function input(): array
    {
        return ['asset_ids' => $this->assetIds];
    }

    protected function execute(AgentRun $run): void
    {
        // TODO: Implementar ciclo 12h del Orquestador General
        // 1. Leer métricas de todos los activos (Umami + tablas propias)
        // 2. Calcular Score Compuesto por activo via ScoreComposite
        // 3. Detectar alertas y umbrales superados
        // 4. Clasificar cada acción en N1, N2, N3
        // 5. N1/N2: ejecutar directamente | N3: insertar en approvals y notificar
        // 6. Generar reporte P&L semanal automático
    }
}
