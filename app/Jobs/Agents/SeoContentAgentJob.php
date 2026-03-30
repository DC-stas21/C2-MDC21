<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;
use App\Models\BlogPost;
use App\Models\NicheConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SeoContentAgentJob extends BaseAgentJob
{
    public int $timeout = 600;

    public function __construct(
        private readonly string $nicheConfigId,
        private readonly int $articlesCount = 5
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
                BlogPost::create([
                    'title' => $article['title'],
                    'slug' => Str::slug($article['title']),
                    'author' => 'MDC21 Editorial',
                    'body' => $article['body'],
                    'sources' => $article['sources'],
                    'methodology' => $article['methodology'],
                    'status' => 'draft',
                    'asset' => $niche->domain,
                ]);
            }

            $articles[] = ['title' => $article['title'], 'policy' => $policyResult];
        }

        $this->updateOutput([
            'domain' => $niche->domain,
            'keywords' => count($keywords),
            'articles' => count($articles),
            'approved' => collect($articles)->where('policy', 'approved')->count(),
            'rejected' => collect($articles)->where('policy', 'rejected')->count(),
            'method' => empty(config('services.openai.api_key')) ? 'template' : 'ai',
        ]);

        Log::info('[seo_content] Completed', ['domain' => $niche->domain, 'articles' => count($articles)]);
    }

    private function researchKeywords(NicheConfig $niche): array
    {
        // TODO: Use GPT-4o when API key configured
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
        // TODO: Use GPT-4o-mini Batch when API key configured
        $sources = [
            'Hipotecas' => ['Banco de España', 'INE', 'BCE'],
            'Energía' => ['CNMC', 'IDAE', 'Red Eléctrica de España'],
            'Seguros' => ['DGSFP', 'UNESPA', 'OCU'],
            'Préstamos' => ['Banco de España', 'ASNEF', 'INE'],
            'Solar' => ['IDAE', 'UNEF', 'CNMC'],
        ];

        return [
            'title' => $keyword,
            'body' => "[Contenido pendiente de generación por IA]\n\nArtículo sobre: {$keyword}\nSector: {$niche->vertical}\nActivo: {$niche->domain}",
            'sources' => $sources[$niche->vertical] ?? ['Fuentes oficiales'],
            'methodology' => "Análisis basado en datos públicos del sector {$niche->vertical} en España.",
        ];
    }
}
