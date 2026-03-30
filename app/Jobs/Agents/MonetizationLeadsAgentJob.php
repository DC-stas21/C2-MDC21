<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;
use App\Models\Approval;
use App\Models\Lead;
use App\Models\NicheConfig;
use Illuminate\Support\Facades\Log;

class MonetizationLeadsAgentJob extends BaseAgentJob
{
    public function __construct(
        private readonly string $nicheConfigId,
        private readonly string $task = 'score_leads'
    ) {
        $this->onQueue('agents');
    }

    protected function agentType(): string
    {
        return 'monetization_leads';
    }

    protected function input(): array
    {
        return ['niche_config_id' => $this->nicheConfigId, 'task' => $this->task];
    }

    protected function execute(AgentRun $run): void
    {
        $niche = NicheConfig::findOrFail($this->nicheConfigId);

        match ($this->task) {
            'score_leads' => $this->scoreLeads($run, $niche),
            'find_providers' => $this->findProviders($niche),
            'generate_proposal' => $this->generateProposal($run, $niche),
            default => throw new \InvalidArgumentException("Unknown task: {$this->task}"),
        };
    }

    private function scoreLeads(AgentRun $run, NicheConfig $niche): void
    {
        $newLeads = Lead::where('asset', $niche->domain)->where('status', 'new')->get();
        $processed = ['qualified' => 0, 'review' => 0, 'discarded' => 0];

        foreach ($newLeads as $lead) {
            if ($lead->score >= 70) {
                $lead->update(['status' => 'qualified']);
                $processed['qualified']++;
            } elseif ($lead->score >= 40) {
                Approval::create([
                    'agent_run_id' => $run->id,
                    'action' => "Revisar lead score {$lead->score}: {$niche->domain}",
                    'level' => 'N3',
                    'status' => 'pending',
                    'reason' => "Lead score {$lead->score} requiere revisión",
                    'context' => ['lead_id' => $lead->id, 'score' => $lead->score, 'domain' => $niche->domain],
                ]);
                $processed['review']++;
            } else {
                $lead->update(['status' => 'discarded']);
                $processed['discarded']++;
            }
        }

        $this->updateOutput(['task' => 'score_leads', 'domain' => $niche->domain, 'total' => $newLeads->count(), ...$processed]);
        Log::info('[monetization] Leads scored', ['domain' => $niche->domain, 'total' => $newLeads->count()]);
    }

    private function findProviders(NicheConfig $niche): void
    {
        $this->updateOutput(['task' => 'find_providers', 'domain' => $niche->domain, 'status' => 'pending_api_key']);
    }

    private function generateProposal(AgentRun $run, NicheConfig $niche): void
    {
        Approval::create([
            'agent_run_id' => $run->id,
            'action' => "Propuesta CPL {$niche->domain}: \${$niche->cpl}/lead",
            'level' => 'N3',
            'status' => 'pending',
            'reason' => 'Propuesta comercial requiere aprobación humana',
            'context' => ['domain' => $niche->domain, 'cpl' => $niche->cpl],
        ]);

        $this->updateOutput(['task' => 'generate_proposal', 'domain' => $niche->domain, 'cpl' => $niche->cpl, 'status' => 'pending_approval']);
    }
}
