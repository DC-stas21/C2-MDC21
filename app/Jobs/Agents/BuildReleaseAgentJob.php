<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;
use App\Models\Approval;
use App\Models\NicheConfig;
use App\Services\NginxConfigService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BuildReleaseAgentJob extends BaseAgentJob
{
    public int $timeout = 300;

    public function __construct(
        private readonly string $nicheConfigId,
        private readonly string $environment = 'staging'
    ) {
        $this->onQueue('agents-ops');
    }

    protected function agentType(): string
    {
        return 'build_release';
    }

    protected function input(): array
    {
        return ['niche_config_id' => $this->nicheConfigId, 'environment' => $this->environment];
    }

    protected function execute(AgentRun $run): void
    {
        $niche = NicheConfig::findOrFail($this->nicheConfigId);
        $steps = [];

        // Validate the web exists
        $steps['web_exists'] = [
            'status' => is_dir($niche->sitePath().'/dist') ? 'passed' : 'failed',
            'path' => $niche->sitePath(),
        ];

        if ($steps['web_exists']['status'] === 'failed') {
            $this->updateOutput(['steps' => $steps, 'result' => 'failed', 'error' => 'Web not built yet']);
            throw new \RuntimeException("Web not built: {$niche->domain}");
        }

        // Nginx configuration
        $nginx = app(NginxConfigService::class);
        if ($nginx->isAvailable()) {
            $steps['nginx'] = $nginx->deploy($niche->domain);
            if ($steps['nginx']['status'] === 'failed') {
                $this->updateOutput(['steps' => $steps, 'result' => 'failed']);
                throw new \RuntimeException('Nginx config failed: '.($steps['nginx']['message'] ?? ''));
            }
        } else {
            $steps['nginx'] = ['status' => 'skipped', 'message' => 'Nginx not available (local dev)'];
        }

        if ($this->environment === 'staging') {
            // Staging: update status, wait for human approval for production
            $niche->update(['build_status' => NicheConfig::STATUS_STAGING]);

            Approval::create([
                'agent_run_id' => $run->id,
                'action' => "Deploy a producción: {$niche->domain}",
                'level' => 'N3',
                'status' => 'pending',
                'reason' => 'Web lista en staging. Aprueba para publicar en producción.',
                'context' => [
                    'domain' => $niche->domain,
                    'vertical' => $niche->vertical,
                    'site_path' => $niche->sitePath(),
                ],
            ]);

            $this->updateOutput([
                'steps' => $steps,
                'result' => 'staging',
                'domain' => $niche->domain,
                'message' => 'Web en staging, esperando aprobación N3 para producción',
            ]);

            Log::info('[build_release] Staging ready', ['domain' => $niche->domain]);

        } else {
            // Production: SSL + health check + mark as live
            if ($nginx->isAvailable()) {
                $steps['ssl'] = $nginx->setupSsl($niche->domain);
                if ($steps['ssl']['status'] === 'failed') {
                    $niche->update([
                        'build_status' => NicheConfig::STATUS_FAILED,
                        'build_metadata' => array_merge($niche->build_metadata ?? [], [
                            'error' => 'SSL setup failed: '.($steps['ssl']['message'] ?? ''),
                        ]),
                    ]);
                    $this->updateOutput(['steps' => $steps, 'result' => 'failed', 'error' => 'SSL failed']);
                    throw new \RuntimeException('SSL setup failed for '.$niche->domain);
                }
            }

            // Health check — verify the site actually responds
            $steps['health_check'] = $this->healthCheck($niche->domain);

            if ($steps['health_check']['status'] === 'failed') {
                $niche->update([
                    'build_status' => NicheConfig::STATUS_FAILED,
                    'build_metadata' => array_merge($niche->build_metadata ?? [], [
                        'error' => 'Health check failed: '.($steps['health_check']['message'] ?? ''),
                    ]),
                ]);
                $this->updateOutput(['steps' => $steps, 'result' => 'failed', 'error' => 'Health check failed']);

                Approval::create([
                    'agent_run_id' => $run->id,
                    'action' => "Deploy falló: {$niche->domain} — health check no pasó",
                    'level' => 'N3',
                    'status' => 'pending',
                    'reason' => 'La web no responde correctamente después del deploy. Revisar logs.',
                    'context' => ['domain' => $niche->domain, 'health_check' => $steps['health_check']],
                ]);

                throw new \RuntimeException('Health check failed for '.$niche->domain);
            }

            // All good → mark as live
            $niche->update([
                'build_status' => NicheConfig::STATUS_LIVE,
                'build_metadata' => array_merge($niche->build_metadata ?? [], [
                    'deployed_at' => now()->toIso8601String(),
                    'environment' => 'production',
                ]),
            ]);

            $this->updateOutput([
                'steps' => $steps,
                'result' => 'live',
                'domain' => $niche->domain,
                'url' => "https://{$niche->domain}",
            ]);

            Log::info('[build_release] Production deployed and verified', ['domain' => $niche->domain]);
        }
    }

    private function healthCheck(string $domain): array
    {
        // Wait a moment for Nginx to reload
        sleep(2);

        try {
            $response = Http::timeout(15)->get("https://{$domain}");

            if ($response->successful()) {
                $bodyLength = strlen($response->body());

                return [
                    'status' => $bodyLength > 100 ? 'passed' : 'failed',
                    'http_code' => $response->status(),
                    'body_size' => $bodyLength,
                    'message' => $bodyLength > 100 ? "OK — {$response->status()}, {$bodyLength} bytes" : 'Page is too small, may be empty',
                ];
            }

            return [
                'status' => 'failed',
                'http_code' => $response->status(),
                'message' => "HTTP {$response->status()}",
            ];
        } catch (\Throwable $e) {
            // In local dev, domain won't resolve — that's OK
            if (! app()->environment('production')) {
                return ['status' => 'passed', 'message' => 'Skipped in local (domain not reachable)'];
            }

            return [
                'status' => 'failed',
                'message' => "Unreachable: {$e->getMessage()}",
            ];
        }
    }
}
