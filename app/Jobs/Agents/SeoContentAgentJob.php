<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;

class SeoContentAgentJob extends BaseAgentJob
{
    public string $queue = 'agents';

    public int $timeout = 600;

    public function __construct(
        private readonly string $nicheConfigId,
        private readonly int $articlesCount = 5
    ) {}

    protected function agentType(): string
    {
        return 'seo_content';
    }

    protected function input(): array
    {
        return [
            'niche_config_id' => $this->nicheConfigId,
            'articles_count' => $this->articlesCount,
        ];
    }

    protected function execute(AgentRun $run): void
    {
        // TODO: Implementar Agente SEO & Contenido
        // 1. gpt-4o investiga keywords y competencia SERP por nicho
        // 2. Genera batch de hasta 20 artículos con gpt-4o-mini Batch API
        // 3. Cada artículo incluye campos E-E-A-T (autoría, fuentes, metodología, fecha)
        // 4. Llamar PolicyBrandAgentJob por cada artículo
        // 5. Artículos aprobados → estado 'draft' en blog_posts
        // 6. Notificar al humano por Telegram/email: borradores listos
    }
}
