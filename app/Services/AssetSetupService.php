<?php

namespace App\Services;

use App\Models\NicheConfig;
use App\Models\Policy;
use App\Models\PromptVersion;
use Illuminate\Support\Facades\Log;

class AssetSetupService
{
    /**
     * Auto-configure an asset after creation.
     * Creates policies and prompts based on the asset's vertical and context.
     */
    public function setup(NicheConfig $asset): array
    {
        $report = [
            'policies_created' => 0,
            'prompts_created' => 0,
        ];

        $report['policies_created'] = $this->createPolicies($asset);
        $report['prompts_created'] = $this->createPrompts($asset);

        Log::info('[asset_setup] Auto-setup completed', [
            'domain' => $asset->domain,
            'policies' => $report['policies_created'],
            'prompts' => $report['prompts_created'],
        ]);

        return $report;
    }

    private function createPolicies(NicheConfig $asset): int
    {
        // Ensure global policies exist
        $this->ensureGlobalPolicies();

        // Create asset-specific policy
        $context = $asset->config ?? [];
        $description = $context['description'] ?? "Contenido sobre {$asset->vertical}";
        $audience = $context['target_audience'] ?? 'público general español';
        $tone = $context['tone'] ?? 'profesional, cercano y educativo';

        $exists = Policy::where('scope', $asset->domain)->exists();
        if ($exists) {
            return 0;
        }

        Policy::create([
            'scope' => $asset->domain,
            'type' => 'content',
            'content' => $this->buildContentPolicy($asset, $description, $audience, $tone),
            'is_active' => true,
            'metadata' => ['auto_generated' => true, 'vertical' => $asset->vertical],
        ]);

        return 1;
    }

    private function ensureGlobalPolicies(): void
    {
        $globals = [
            [
                'type' => 'tone',
                'content' => 'El tono debe ser profesional, cercano y educativo. Nunca agresivo comercialmente. Evitar superlativos ("el mejor", "único", "garantizado"). Siempre usar "tú" informal, nunca "usted".',
            ],
            [
                'type' => 'legal',
                'content' => 'Nunca dar asesoramiento financiero directo. Siempre incluir disclaimer: "Esta información es orientativa y no constituye asesoramiento profesional." No prometer resultados ni rentabilidades.',
            ],
            [
                'type' => 'brand',
                'content' => 'No mencionar competidores por nombre. No usar claims no verificables. No incluir testimonios falsos. Toda estadística debe citar su fuente.',
            ],
            [
                'type' => 'privacy',
                'content' => 'Nunca exponer datos personales de usuarios. Los ejemplos deben usar datos ficticios. Cumplir RGPD en todo contenido que implique datos personales.',
            ],
            [
                'type' => 'seo',
                'content' => 'Artículos: título H1 con keyword principal, mínimo 3 subtítulos H2, fuentes verificables, fecha de actualización, nombre de autor. Extensión mínima 800 palabras.',
            ],
        ];

        foreach ($globals as $policy) {
            Policy::firstOrCreate(
                ['scope' => 'global', 'type' => $policy['type']],
                array_merge($policy, ['scope' => 'global', 'is_active' => true, 'metadata' => ['auto_generated' => true]])
            );
        }
    }

    private function buildContentPolicy(NicheConfig $asset, string $description, string $audience, string $tone): string
    {
        $verticalRules = $this->getVerticalRules($asset->vertical);

        return implode(' ', [
            "Contenido para {$asset->domain} ({$asset->vertical}).",
            "Descripción: {$description}.",
            "Audiencia: {$audience}.",
            "Tono: {$tone}.",
            $verticalRules,
        ]);
    }

    private function getVerticalRules(string $vertical): string
    {
        return match ($vertical) {
            'Hipotecas' => 'Siempre referenciar datos del Banco de España, INE o BCE. Incluir simulaciones con tipos reales. Advertir sobre variabilidad del Euríbor.',
            'Energía' => 'Datos basados en CNMC. Simulaciones con consumo medio español (3.500 kWh/año). No favorecer ninguna compañía eléctrica.',
            'Seguros' => 'No prometer precios concretos, usar rangos. Explicar coberturas y exclusiones con claridad. Recomendar comparar.',
            'Préstamos' => 'Obligatorio explicar TAE, no solo TIN. Advertir sobre endeudamiento. Nunca promover préstamos rápidos de alto interés.',
            'Solar' => 'ROI calculado con datos IDAE. Incluir subvenciones vigentes. Distinguir entre autoconsumo con y sin excedentes.',
            default => "Contenido verificado con fuentes oficiales del sector {$vertical} en España.",
        };
    }

    private function createPrompts(NicheConfig $asset): int
    {
        $context = $asset->config ?? [];
        $description = $context['description'] ?? "web sobre {$asset->vertical}";
        $audience = $context['target_audience'] ?? 'público general';
        $tone = $context['tone'] ?? 'profesional y cercano';
        $keywords = $context['keywords'] ?? "{$asset->vertical} España";

        $agents = [
            [
                'agent_type' => 'policy_brand',
                'model' => 'claude-haiku-4-5',
                'prompt' => "Eres el agente Policy & Brand de MDC21 para {$asset->domain}.\n\nCONTEXTO DEL ACTIVO:\n- Dominio: {$asset->domain}\n- Vertical: {$asset->vertical}\n- Descripción: {$description}\n- Audiencia: {$audience}\n- Tono: {$tone}\n\nTu trabajo es validar que el contenido cumple las políticas activas. Analiza cada pieza contra TODAS las reglas y responde en JSON:\n{\"decision\":\"approved|rejected\",\"reason\":\"...\",\"violations\":[]}",
            ],
            [
                'agent_type' => 'seo_content',
                'model' => 'gpt-4o-mini',
                'prompt' => "Eres un redactor SEO experto en {$asset->vertical} para el mercado español.\n\nACTIVO: {$asset->domain}\nDESCRIPCIÓN: {$description}\nAUDIENCIA: {$audience}\nTONO: {$tone}\nKEYWORDS FOCO: {$keywords}\n\nReglas:\n- Artículos E-E-A-T: autoría, fuentes verificables, metodología, fecha\n- Mínimo 800 palabras\n- H1 con keyword principal + mínimo 3 H2\n- Incluir datos de fuentes oficiales españolas\n- Tono {$tone}, nunca agresivo comercialmente\n- Incluir disclaimer legal si aplica",
            ],
            [
                'agent_type' => 'distribution',
                'model' => 'claude-sonnet-4-5',
                'prompt' => "Eres el agente de Distribución de MDC21 para {$asset->domain}.\n\nACTIVO: {$asset->domain} ({$asset->vertical})\nAUDIENCIA: {$audience}\nTONO: {$tone}\n\nGenera contenido listo para copiar y publicar en redes sociales.\nIncluye: texto completo + dónde publicar + por qué ahora + nivel de riesgo reputacional.\nNUNCA publiques automáticamente. Prepara para que el humano copie y publique.",
            ],
            [
                'agent_type' => 'engagement_retention',
                'model' => 'gpt-4o-mini',
                'prompt' => "Eres el agente de Engagement de MDC21 para {$asset->domain}.\n\nACTIVO: {$asset->domain} ({$asset->vertical})\nAUDIENCIA: {$audience}\nTONO: {$tone}\n\nTareas:\n- Newsletters mensuales con novedades del sector\n- FAQs basadas en preguntas reales del vertical\n- Secuencias drip para nuevos suscriptores\n- Contenido para PDFs descargables",
            ],
            [
                'agent_type' => 'orchestrator',
                'model' => 'claude-sonnet-4-5',
                'prompt' => "Eres el Orquestador General de MDC21. Supervisas el portafolio de activos digitales.\n\nAnaliza las métricas de cada activo, calcula el Score Compuesto (6 dimensiones), detecta alertas y clasifica acciones:\n- N1 (automático): análisis, scoring, borradores\n- N2 (semiautomático): staging, blog propio\n- N3 (humano siempre): redes, producción, contacto externo\n\nGenera un reporte claro con prioridades y acciones recomendadas.",
            ],
        ];

        $created = 0;
        foreach ($agents as $agent) {
            $exists = PromptVersion::where('agent_type', $agent['agent_type'])
                ->where('is_active', true)
                ->exists();

            if (! $exists) {
                PromptVersion::create([
                    'agent_type' => $agent['agent_type'],
                    'version' => 1,
                    'model' => $agent['model'],
                    'prompt_text' => $agent['prompt'],
                    'is_active' => true,
                    'metrics' => ['auto_generated' => true, 'asset' => $asset->domain],
                ]);
                $created++;
            }
        }

        return $created;
    }
}
