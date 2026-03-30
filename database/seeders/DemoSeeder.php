<?php

namespace Database\Seeders;

use App\Models\AgentRun;
use App\Models\Approval;
use App\Models\Lead;
use App\Models\NicheConfig;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedNicheConfigs();
        $this->seedAgentRuns();
        $this->seedApprovals();
        $this->seedLeads();
    }

    private function seedNicheConfigs(): void
    {
        $niches = [
            ['domain' => 'calculahipoteca.es', 'vertical' => 'Hipotecas', 'cpl' => 12.50, 'is_active' => true],
            ['domain' => 'comparaenergia.es', 'vertical' => 'Energía', 'cpl' => 8.75, 'is_active' => true],
            ['domain' => 'segurobarato.net', 'vertical' => 'Seguros', 'cpl' => 15.00, 'is_active' => true],
            ['domain' => 'prestamoya.com', 'vertical' => 'Préstamos', 'cpl' => 18.00, 'is_active' => true],
            ['domain' => 'placasolar.info', 'vertical' => 'Solar', 'cpl' => 22.50, 'is_active' => false],
        ];

        $scores = [72.4, 85.1, 58.3, 91.0, null];

        foreach ($niches as $i => $niche) {
            $config = NicheConfig::create(array_merge($niche, [
                'colors' => ['primary' => '#4f46e5', 'secondary' => '#0ea5e9'],
                'config' => ['locale' => 'es', 'currency' => 'EUR'],
            ]));

            if ($scores[$i] !== null) {
                Cache::put("score_composite:{$config->id}", $scores[$i], 86400);
            }
        }
    }

    private function seedAgentRuns(): void
    {
        $agentTypes = [
            'orchestrator', 'policy_brand', 'seo_content', 'distribution',
            'engagement_retention', 'monetization_leads', 'build_release',
            'infra_reliability', 'qa_experimentation',
        ];

        $statuses = ['completed', 'completed', 'completed', 'completed', 'failed', 'running', 'pending'];

        // Last 7 days of activity
        for ($day = 6; $day >= 0; $day--) {
            $date = now()->subDays($day);
            $runsPerDay = rand(8, 18);

            for ($j = 0; $j < $runsPerDay; $j++) {
                $status = $day === 0 && $j >= $runsPerDay - 2
                    ? ($j === $runsPerDay - 1 ? 'running' : 'pending')
                    : $statuses[array_rand($statuses)];

                $agentType = $agentTypes[array_rand($agentTypes)];
                $startedAt = $date->copy()->addMinutes(rand(0, 1380));
                $duration = rand(5, 300);

                AgentRun::create([
                    'agent_type' => $agentType,
                    'status' => $status,
                    'input' => ['task' => 'auto', 'trigger' => 'scheduler'],
                    'output' => $status === 'completed' ? ['result' => 'ok', 'items_processed' => rand(1, 50)] : null,
                    'metadata' => ['queue' => str_contains($agentType, 'build') || str_contains($agentType, 'infra') || str_contains($agentType, 'qa') ? 'agents-ops' : 'agents'],
                    'error' => $status === 'failed' ? $this->randomError() : null,
                    'started_at' => $startedAt,
                    'finished_at' => in_array($status, ['completed', 'failed']) ? $startedAt->copy()->addSeconds($duration) : null,
                    'created_at' => $startedAt,
                ]);
            }
        }
    }

    private function seedApprovals(): void
    {
        $runs = AgentRun::whereIn('agent_type', ['distribution', 'seo_content', 'monetization_leads', 'build_release'])
            ->where('status', 'completed')
            ->inRandomOrder()
            ->take(12)
            ->get();

        $actions = [
            'Publicar artículo: "Cómo calcular tu hipoteca en 2026"',
            'Deploy a producción v2.4.1',
            'Enviar propuesta CPL a BrokerSeguro',
            'Publicar newsletter mensual — Marzo 2026',
            'Deploy hotfix: corrección formulario contacto',
            'Artículo SEO: "Comparativa energéticas 2026"',
            'Propuesta partnership con EnergíaVerde',
            'Campaña drip: leads fríos reactivación',
            'Publicar en LinkedIn: caso de éxito hipotecas',
            'Deploy staging: nueva calculadora solar',
            'Artículo: "Guía seguros de hogar 2026"',
            'Contacto directo: lead premium score 92',
        ];

        foreach ($runs as $i => $run) {
            $isPending = $i < 5;

            Approval::create([
                'agent_run_id' => $run->id,
                'action' => $actions[$i] ?? $actions[0],
                'level' => 'N3',
                'status' => $isPending ? 'pending' : (rand(0, 4) > 0 ? 'approved' : 'denied'),
                'requested_by' => null,
                'decided_by' => $isPending ? null : User::first()?->id,
                'reason' => 'Requiere aprobación humana antes de ejecución',
                'decision_note' => $isPending ? null : 'OK, aprobado',
                'context' => ['source' => $run->agent_type, 'priority' => rand(1, 3)],
                'decided_at' => $isPending ? null : now()->subHours(rand(1, 48)),
            ]);
        }
    }

    private function seedLeads(): void
    {
        $providers = ['BrokerHipoteca', 'SeguroDirecto', 'EnergíaPlus', 'PréstamoRápido', 'SolarTech'];
        $assets = ['calculahipoteca.es', 'comparaenergia.es', 'segurobarato.net', 'prestamoya.com'];
        $statuses = ['new', 'qualified', 'sent', 'discarded'];

        for ($i = 0; $i < 35; $i++) {
            Lead::create([
                'asset' => $assets[array_rand($assets)],
                'provider' => $providers[array_rand($providers)],
                'score' => rand(15, 98),
                'status' => $statuses[array_rand($statuses)],
                'data' => [
                    'name' => 'Lead #'.($i + 1),
                    'email' => 'lead'.($i + 1).'@ejemplo.com',
                    'phone' => '+34 6'.rand(10, 99).' '.rand(100, 999).' '.rand(100, 999),
                ],
                'created_at' => now()->subDays(rand(0, 14))->subHours(rand(0, 23)),
            ]);
        }
    }

    private function randomError(): string
    {
        $errors = [
            'Rate limit exceeded: Claude API (429)',
            'Timeout after 600s: SERP research',
            'OpenAI Batch API: invalid request format',
            'Connection refused: Redis unavailable',
            'Lighthouse CI: Performance score 52 < 60 threshold',
        ];

        return $errors[array_rand($errors)];
    }
}
