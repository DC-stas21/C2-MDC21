<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;
use App\Models\Approval;
use App\Models\NicheConfig;
use Illuminate\Support\Facades\Log;

class EngagementRetentionAgentJob extends BaseAgentJob
{
    public function __construct(
        private readonly string $nicheConfigId,
        private readonly string $task = 'newsletter'
    ) {
        $this->onQueue('agents');
    }

    protected function agentType(): string
    {
        return 'engagement_retention';
    }

    protected function input(): array
    {
        return ['niche_config_id' => $this->nicheConfigId, 'task' => $this->task];
    }

    protected function execute(AgentRun $run): void
    {
        $niche = NicheConfig::findOrFail($this->nicheConfigId);

        match ($this->task) {
            'newsletter' => $this->handleNewsletter($run, $niche),
            'faq' => $this->handleFAQ($run, $niche),
            'drip' => $this->handleDrip($niche),
            'pdf' => $this->handlePDF($niche),
            'ab_test' => $this->handleABTest($niche),
            default => throw new \InvalidArgumentException("Unknown task: {$this->task}"),
        };
    }

    private function handleNewsletter(AgentRun $run, NicheConfig $niche): void
    {
        $subject = "Novedades {$niche->vertical} — ".now()->format('F Y');
        $body = "Hola,\n\nEste mes en {$niche->domain} te traemos las últimas novedades del sector {$niche->vertical}.\n\n[Contenido generado por IA cuando API keys estén configuradas]\n\nEquipo MDC21";

        // Policy check
        try {
            $policyJob = new PolicyBrandAgentJob($body, 'newsletter', $run->id, $niche->domain);
            $policyJob->handle();
        } catch (\Throwable $e) {
            Log::warning('[engagement] Policy check failed', ['error' => $e->getMessage()]);
        }

        Approval::create([
            'agent_run_id' => $run->id,
            'action' => "Newsletter: {$niche->domain}",
            'level' => 'N3',
            'status' => 'pending',
            'reason' => 'Newsletter requiere revisión y envío manual',
            'context' => ['subject' => $subject, 'domain' => $niche->domain],
        ]);

        $this->updateOutput(['task' => 'newsletter', 'domain' => $niche->domain, 'subject' => $subject, 'status' => 'pending_human']);
    }

    private function handleFAQ(AgentRun $run, NicheConfig $niche): void
    {
        $faqs = [
            'Hipotecas' => [['q' => '¿Cuánto puedo pedir?', 'a' => 'Hasta el 80% del valor de tasación.'], ['q' => '¿Fija o variable?', 'a' => 'Depende de tu perfil de riesgo.']],
            'Energía' => [['q' => '¿Regulado o libre?', 'a' => 'Compara según tu consumo.'], ['q' => '¿Cuánto cuestan paneles solares?', 'a' => 'Entre 4.000€ y 8.000€.']],
        ];

        $this->updateOutput(['task' => 'faq', 'domain' => $niche->domain, 'faqs' => $faqs[$niche->vertical] ?? []]);
    }

    private function handleDrip(NicheConfig $niche): void
    {
        $this->updateOutput([
            'task' => 'drip',
            'domain' => $niche->domain,
            'status' => 'ready_for_activation',
            'note' => 'Requires Resend API key and initial human activation',
        ]);
    }

    private function handlePDF(NicheConfig $niche): void
    {
        $this->updateOutput(['task' => 'pdf', 'domain' => $niche->domain, 'status' => 'pending_content']);
    }

    private function handleABTest(NicheConfig $niche): void
    {
        $this->updateOutput(['task' => 'ab_test', 'domain' => $niche->domain, 'status' => 'pending_setup']);
    }
}
