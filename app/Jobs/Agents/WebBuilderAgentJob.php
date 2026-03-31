<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;
use App\Models\Artifact;
use App\Models\NicheConfig;
use App\Services\AI\ClaudeService;
use App\Services\NginxConfigService;
use App\Services\WebConfigTemplateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

class WebBuilderAgentJob extends BaseAgentJob
{
    public int $timeout = 900;

    public function __construct(
        private readonly string $nicheConfigId
    ) {
        $this->onQueue('agents-ops');
    }

    protected function agentType(): string
    {
        return 'web_builder';
    }

    protected function input(): array
    {
        return ['niche_config_id' => $this->nicheConfigId];
    }

    protected function execute(AgentRun $run): void
    {
        $niche = NicheConfig::findOrFail($this->nicheConfigId);

        // Prevent concurrent builds for same domain
        $lock = Cache::lock("web-build:{$niche->domain}", $this->timeout);
        if (! $lock->get()) {
            throw new \RuntimeException("Build already in progress for {$niche->domain}");
        }

        try {
            $this->doBuild($run, $niche);
        } finally {
            $lock->release();
        }
    }

    private function doBuild(AgentRun $run, NicheConfig $niche): void
    {
        $steps = [];
        $niche->update(['build_status' => NicheConfig::STATUS_BUILDING]);

        // Step 1: Generate site.config.json
        $steps['generate_config'] = $this->generateConfig($niche);
        if ($steps['generate_config']['status'] === 'failed') {
            $this->failBuild($niche, $steps, $steps['generate_config']['message']);

            return;
        }

        $siteConfig = $steps['generate_config']['config'];

        // Step 2: Validate config
        $steps['validate'] = $this->validateConfig($siteConfig);
        if ($steps['validate']['status'] === 'failed') {
            // Fallback to template
            Log::warning('[web_builder] Config validation failed, using template fallback');
            $siteConfig = app(WebConfigTemplateService::class)->generateForNiche($niche);
            $steps['validate'] = ['status' => 'ok', 'fallback' => true];
        }

        // Step 3: Copy template to target
        $targetPath = $niche->sitePath();
        $steps['copy_template'] = $this->copyTemplate($targetPath);
        if ($steps['copy_template']['status'] === 'failed') {
            $this->failBuild($niche, $steps, $steps['copy_template']['message']);

            return;
        }

        // Step 4: Write site.config.json
        $siteConfig['build']['generated_at'] = now()->toIso8601String();
        $siteConfig['build']['generator'] = 'web_builder_agent';
        $siteConfig['build']['agent_run_id'] = $run->id;
        File::put("{$targetPath}/site.config.json", json_encode($siteConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Step 5: Install dependencies and build
        $steps['npm_build'] = $this->buildSite($targetPath);
        if ($steps['npm_build']['status'] === 'failed') {
            $this->failBuild($niche, $steps, $steps['npm_build']['message']);

            return;
        }

        // Step 6: Configure Nginx (skip in local dev)
        $nginx = app(NginxConfigService::class);
        if ($nginx->isAvailable()) {
            $steps['nginx'] = $nginx->deploy($niche->domain);
        } else {
            $steps['nginx'] = ['status' => 'skipped', 'message' => 'Nginx not available (local dev)'];
        }

        // Step 7: Store artifacts
        Artifact::create([
            'agent_run_id' => $run->id,
            'type' => 'site_config',
            'path' => "{$targetPath}/site.config.json",
            'metadata' => ['domain' => $niche->domain, 'config_version' => $siteConfig['build']['config_version'] ?? 1],
        ]);

        // Step 8: Update status
        $niche->update([
            'build_status' => NicheConfig::STATUS_STAGING,
            'build_metadata' => [
                'last_build_at' => now()->toIso8601String(),
                'agent_run_id' => $run->id,
                'config_version' => $siteConfig['build']['config_version'] ?? 1,
                'target_path' => $targetPath,
            ],
        ]);

        $this->updateOutput([
            'domain' => $niche->domain,
            'steps' => $steps,
            'result' => 'staging',
            'target_path' => $targetPath,
            'config_method' => $steps['generate_config']['method'] ?? 'template',
        ]);

        Log::info('[web_builder] Build completed', ['domain' => $niche->domain, 'status' => 'staging']);

        // Step 9: Chain to PolicyBrand for content validation
        $configJson = json_encode($siteConfig, JSON_UNESCAPED_UNICODE);
        PolicyBrandAgentJob::dispatch($configJson, 'site_config', $run->id, $niche->domain);
    }

    private function generateConfig(NicheConfig $niche): array
    {
        $apiKey = config('services.claude.api_key');

        if (! empty($apiKey)) {
            try {
                return $this->generateWithAI($niche);
            } catch (\Throwable $e) {
                Log::warning('[web_builder] AI generation failed, using template', ['error' => $e->getMessage()]);
            }
        }

        // Template fallback
        $config = app(WebConfigTemplateService::class)->generateForNiche($niche);

        return ['status' => 'ok', 'method' => 'template', 'config' => $config];
    }

    private function generateWithAI(NicheConfig $niche): array
    {
        $context = $niche->config ?? [];
        $claude = app(ClaudeService::class);

        $systemPrompt = <<<'PROMPT'
Eres el Web Builder Agent de MDC21. Generas configuraciones completas de webs en formato JSON.

El JSON debe seguir exactamente este schema:
{
  "meta": { "domain": string, "vertical": string, "language": "es", "title": string, "description": string },
  "design": { "colors": { "primary": hex, "secondary": hex, "accent": hex, "background": hex, "surface": hex, "text": hex, "text_muted": hex }, "fonts": { "heading": string, "body": string }, "border_radius": string, "style": string },
  "navigation": [{ "label": string, "slug": string }],
  "pages": [{ "slug": string, "type": string, "seo": { "title": string, "description": string }, "sections": [{ "type": "hero"|"features"|"content"|"faq"|"cta"|"tool"|"lead_form", ...props }] }],
  "footer": { "copyright": string, "links": [{ "label": string, "slug": string }], "disclaimer": string },
  "tools": { "lead_form": { "enabled": boolean, "endpoint": string, "fields": string[], "asset_domain": string } },
  "build": { "generated_at": "", "generator": "ai", "config_version": 1 }
}

Section types y sus props:
- hero: headline, subtitle, cta: { text, link }
- features: title, items: [{ icon, title, description }]
- content: title, body (HTML)
- faq: title, items: [{ question, answer }]
- cta: title, subtitle, button: { text, link }
- tool: tool_type: "calculator", tool_config: { calculator_type, fields: [{ name, label, type, min, max, step, default }], formula, output_fields, disclaimer }

Genera un diseño moderno y profesional. Los colores deben ser coherentes con el vertical. El contenido debe ser real, útil y en español de España. Incluye páginas: home, herramienta, contacto, aviso-legal, privacidad, cookies.

Responde SOLO con el JSON válido, sin explicaciones.
PROMPT;

        $prompt = "Genera la configuración completa para:\n"
            ."Dominio: {$niche->domain}\n"
            ."Vertical: {$niche->vertical}\n"
            .'Descripción: '.($context['description'] ?? '')."\n"
            .'Audiencia: '.($context['target_audience'] ?? '')."\n"
            .'Tono: '.($context['tone'] ?? 'profesional y cercano')."\n"
            .'Keywords: '.($context['keywords'] ?? '');

        $response = $claude->message(
            prompt: $prompt,
            model: config('services.claude.model_orchestrator', 'claude-sonnet-4-5'),
            maxTokens: 8192,
            systemPrompt: [$systemPrompt],
            useCache: true
        );

        $text = $claude->extractText($response);
        $config = json_decode($text, true);

        if (! $config || ! isset($config['meta'], $config['pages'])) {
            throw new \RuntimeException('AI returned invalid config JSON');
        }

        return ['status' => 'ok', 'method' => 'ai', 'config' => $config, 'usage' => $response['usage'] ?? null];
    }

    private function validateConfig(array $config): array
    {
        $required = ['meta', 'design', 'navigation', 'pages', 'footer'];
        $missing = [];

        foreach ($required as $key) {
            if (! isset($config[$key])) {
                $missing[] = $key;
            }
        }

        if (! empty($missing)) {
            return ['status' => 'failed', 'message' => 'Missing keys: '.implode(', ', $missing)];
        }

        if (empty($config['pages'])) {
            return ['status' => 'failed', 'message' => 'No pages defined'];
        }

        return ['status' => 'ok'];
    }

    private function copyTemplate(string $targetPath): array
    {
        try {
            $stubPath = base_path('stubs/web-template');

            if (! File::isDirectory($stubPath)) {
                return ['status' => 'failed', 'message' => 'Template directory not found'];
            }

            // Create target directory
            if (! File::isDirectory($targetPath)) {
                File::makeDirectory($targetPath, 0755, true);
            }

            // Copy template files (excluding node_modules and dist)
            File::copyDirectory($stubPath, $targetPath);

            // Remove any existing dist or node_modules from copy
            if (File::isDirectory("{$targetPath}/node_modules")) {
                File::deleteDirectory("{$targetPath}/node_modules");
            }
            if (File::isDirectory("{$targetPath}/dist")) {
                File::deleteDirectory("{$targetPath}/dist");
            }

            return ['status' => 'ok', 'path' => $targetPath];
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    private function buildSite(string $targetPath): array
    {
        try {
            // npm install
            $install = Process::timeout(120)->path($targetPath)->run('npm install --no-audit --no-fund 2>&1');
            if ($install->failed()) {
                return ['status' => 'failed', 'step' => 'npm_install', 'message' => mb_substr($install->output(), -300)];
            }

            // vite build
            $build = Process::timeout(120)->path($targetPath)->run('npx vite build 2>&1');
            if ($build->failed()) {
                return ['status' => 'failed', 'step' => 'vite_build', 'message' => mb_substr($build->output(), -300)];
            }

            // Verify dist exists
            if (! File::isDirectory("{$targetPath}/dist")) {
                return ['status' => 'failed', 'step' => 'verify', 'message' => 'dist directory not created'];
            }

            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            return ['status' => 'failed', 'message' => $e->getMessage()];
        }
    }

    private function failBuild(NicheConfig $niche, array $steps, string $error): void
    {
        $niche->update([
            'build_status' => NicheConfig::STATUS_FAILED,
            'build_metadata' => [
                'last_build_at' => now()->toIso8601String(),
                'error' => $error,
            ],
        ]);

        $this->updateOutput([
            'domain' => $niche->domain,
            'steps' => $steps,
            'result' => 'failed',
            'error' => $error,
        ]);

        Log::error('[web_builder] Build failed', ['domain' => $niche->domain, 'error' => $error]);
    }
}
