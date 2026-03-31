<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;
use App\Models\Approval;
use App\Models\NicheConfig;
use Illuminate\Support\Facades\Log;

class DistributionAgentJob extends BaseAgentJob
{
    public function __construct(
        private readonly string $nicheConfigId,
        private readonly string $channel,
        private readonly ?string $contentTitle = null
    ) {
        $this->onQueue('agents');
    }

    protected function agentType(): string
    {
        return 'distribution';
    }

    protected function input(): array
    {
        return ['niche_config_id' => $this->nicheConfigId, 'channel' => $this->channel];
    }

    protected function execute(AgentRun $run): void
    {
        $niche = NicheConfig::findOrFail($this->nicheConfigId);
        $title = $this->contentTitle ?? "Contenido sobre {$niche->vertical}";

        $content = match ($this->channel) {
            'linkedin' => "¿Sabías que...?\n\n{$title}\n\nEn {$niche->domain} hemos analizado los datos más recientes del sector {$niche->vertical}.\n\nLee más en {$niche->domain}",
            'twitter' => "{$title}\n\nDatos actualizados para España.\n\n{$niche->domain}",
            default => "Nuevo contenido en {$niche->domain}: {$title}",
        };

        // Policy validation
        try {
            $policyJob = new PolicyBrandAgentJob($content, 'social_'.$this->channel, $run->id, $niche->domain);
            $policyJob->handle();
        } catch (\Throwable $e) {
            Log::warning('[distribution] Policy check failed', ['error' => $e->getMessage()]);
        }

        // Always N3 — human copies and publishes
        Approval::create([
            'agent_run_id' => $run->id,
            'action' => "Publicar en {$this->channel}: {$niche->domain}",
            'level' => 'N3',
            'status' => 'pending',
            'reason' => 'Distribución en redes siempre requiere publicación manual',
            'context' => ['channel' => $this->channel, 'domain' => $niche->domain, 'content' => $content],
        ]);

        $this->updateOutput([
            'channel' => $this->channel,
            'domain' => $niche->domain,
            'content_length' => strlen($content),
            'awaiting_human' => true,
        ]);

        Log::info('[distribution] Content prepared', ['channel' => $this->channel, 'domain' => $niche->domain]);
    }
}
