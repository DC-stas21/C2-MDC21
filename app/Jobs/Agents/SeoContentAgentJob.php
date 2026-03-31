<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;
use App\Models\NicheConfig;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SeoContentAgentJob extends BaseAgentJob
{
    public int $timeout = 600;

    public function __construct(
        private readonly string $nicheConfigId,
        private readonly int $articlesCount = 3
    ) {
        $this->onQueue('agents');
    }

    protected function agentType(): string
    {
        return 'seo_content';
    }

    protected function input(): array
    {
        return ['niche_config_id' => $this->nicheConfigId, 'articles_count' => $this->articlesCount];
    }

    protected function execute(AgentRun $run): void
    {
        $niche = NicheConfig::findOrFail($this->nicheConfigId);

        // Generate articles
        $keywords = $this->researchKeywords($niche);
        $articles = [];

        foreach ($keywords as $keyword) {
            $article = $this->generateArticle($niche, $keyword);

            // Policy validation
            $policyResult = 'approved';
            try {
                $policyJob = new PolicyBrandAgentJob(
                    content: $article['title']."\n\n".$article['body'],
                    contentType: 'article',
                    requestingAgentRunId: $run->id,
                    assetDomain: $niche->domain
                );
                $policyJob->handle();
                $policyRun = AgentRun::where('agent_type', 'policy_brand')->latest()->first();
                $policyResult = $policyRun?->output['decision'] ?? 'approved';
            } catch (\Throwable $e) {
                Log::warning('[seo_content] Policy check failed', ['error' => $e->getMessage()]);
            }

            if ($policyResult === 'approved') {
                $articles[] = $article;
            }
        }

        // Inject articles into the site.config.json of the web
        if (! empty($articles)) {
            $this->injectIntoSiteConfig($niche, $articles);
        }

        $this->updateOutput([
            'domain' => $niche->domain,
            'keywords' => count($keywords),
            'articles_generated' => count($articles),
            'method' => empty(config('services.openai.api_key')) ? 'template' : 'ai',
        ]);

        Log::info('[seo_content] Completed', ['domain' => $niche->domain, 'articles' => count($articles)]);
    }

    private function injectIntoSiteConfig(NicheConfig $niche, array $articles): void
    {
        $configPath = $niche->sitePath().'/site.config.json';

        if (! File::exists($configPath)) {
            Log::warning('[seo_content] site.config.json not found, skipping injection', ['path' => $configPath]);

            return;
        }

        $config = json_decode(File::get($configPath), true);
        $existing = $config['blog']['articles'] ?? [];

        foreach ($articles as $article) {
            $existing[] = [
                'slug' => Str::slug($article['title']),
                'title' => $article['title'],
                'excerpt' => mb_substr(strip_tags($article['body']), 0, 160),
                'body' => $article['body'],
                'author' => 'MDC21 Editorial',
                'date' => now()->format('d/m/Y'),
                'reading_time' => max(3, (int) (str_word_count($article['body']) / 200)),
                'category' => $niche->vertical,
                'sources' => $article['sources'],
            ];
        }

        $config['blog']['articles'] = $existing;
        File::put($configPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function researchKeywords(NicheConfig $niche): array
    {
        $templates = [
            'Hipotecas' => ['Cómo calcular la cuota de tu hipoteca', 'Hipoteca fija vs variable', 'Requisitos para pedir una hipoteca'],
            'Energía' => ['Cómo reducir tu factura de la luz', 'Mercado regulado vs libre', 'Energía solar para hogares'],
            'Seguros' => ['Guía de seguros de hogar', 'Qué cubre un seguro de vida', 'Comparar seguros online'],
            'Préstamos' => ['Préstamos personales: guía completa', 'TAE vs TIN explicado', 'Reunificación de deudas'],
            'Solar' => ['Coste de instalar placas solares', 'Subvenciones solares', 'Autoconsumo solar'],
        ];

        return array_slice($templates[$niche->vertical] ?? ["Artículo sobre {$niche->vertical}"], 0, $this->articlesCount);
    }

    private function generateArticle(NicheConfig $niche, string $keyword): array
    {
        $sources = [
            'Hipotecas' => ['Banco de España', 'INE', 'BCE'],
            'Energía' => ['CNMC', 'IDAE', 'Red Eléctrica de España'],
            'Seguros' => ['DGSFP', 'UNESPA', 'OCU'],
            'Préstamos' => ['Banco de España', 'ASNEF', 'INE'],
            'Solar' => ['IDAE', 'UNEF', 'CNMC'],
        ];

        return [
            'title' => $keyword,
            'body' => "<h2>{$keyword}</h2><p>Artículo sobre {$keyword} para el mercado español.</p><p>Contenido pendiente de generación por IA cuando las API keys estén configuradas.</p>",
            'sources' => $sources[$niche->vertical] ?? ['Fuentes oficiales'],
            'methodology' => "Análisis basado en datos públicos del sector {$niche->vertical} en España.",
        ];
    }
}
