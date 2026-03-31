<?php

namespace App\Jobs\Agents;

use App\Models\AgentRun;
use App\Models\Approval;
use App\Models\NicheConfig;
use App\Models\Policy;
use App\Services\AI\ClaudeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PolicyBrandAgentJob extends BaseAgentJob
{
    public int $timeout = 120;

    public function __construct(
        private readonly string $content,
        private readonly string $contentType,
        private readonly string $requestingAgentRunId,
        private readonly ?string $assetDomain = null
    ) {
        $this->onQueue('agents');
    }

    protected function agentType(): string
    {
        return 'policy_brand';
    }

    protected function input(): array
    {
        return [
            'content_type' => $this->contentType,
            'content_length' => strlen($this->content),
            'requesting_agent_run_id' => $this->requestingAgentRunId,
            'asset_domain' => $this->assetDomain,
        ];
    }

    protected function execute(AgentRun $run): void
    {
        // 1. Load active policies
        $policies = $this->loadPolicies();

        if ($policies->isEmpty()) {
            $this->updateOutput([
                'decision' => 'approved',
                'reason' => 'No active policies found — auto-approved',
                'method' => 'no_policies',
                'policies_checked' => 0,
            ]);

            return;
        }

        // 2. Try AI validation (Claude Haiku), fallback to rule-based
        $apiKey = config('services.claude.api_key');
        if (! empty($apiKey)) {
            $result = $this->validateWithAI($policies, $apiKey);
        } else {
            $result = $this->validateWithRules($policies);
        }

        // 3. Store result
        $this->updateOutput($result);

        $this->updateMetadata([
            'method' => $result['method'],
            'decision' => $result['decision'],
        ]);

        // 4. If approved + site_config → chain to QA(qa_web)
        if ($result['decision'] === 'approved' && $this->contentType === 'site_config' && $this->assetDomain) {
            $niche = NicheConfig::where('domain', $this->assetDomain)->first();
            if ($niche) {
                QAExperimentationAgentJob::dispatch($niche->id, 'qa_web');
                Log::info('[policy_brand] Site config approved, dispatching QA', ['domain' => $this->assetDomain]);
            }
        }

        // 5. If rejected → create N3 approval for human override
        if ($result['decision'] === 'rejected') {
            Approval::create([
                'agent_run_id' => $run->id,
                'action' => "Policy rechazó: {$this->contentType}".($this->assetDomain ? " para {$this->assetDomain}" : ''),
                'level' => 'N3',
                'status' => 'pending',
                'reason' => $result['reason'],
                'context' => [
                    'content_preview' => mb_substr($this->content, 0, 200),
                    'violations' => $result['violations'] ?? [],
                    'requesting_agent_run_id' => $this->requestingAgentRunId,
                ],
            ]);

            Log::warning('[policy_brand] Content rejected', [
                'content_type' => $this->contentType,
                'asset' => $this->assetDomain,
                'reason' => $result['reason'],
            ]);
        }
    }

    private function loadPolicies(): Collection
    {
        $query = Policy::where('is_active', true);

        if ($this->assetDomain) {
            $query->where(function ($q) {
                $q->where('scope', 'global')
                    ->orWhere('scope', $this->assetDomain);
            });
        } else {
            $query->where('scope', 'global');
        }

        return $query->get();
    }

    private function validateWithAI(Collection $policies, string $apiKey): array
    {
        $policyText = $policies->map(function (Policy $p) {
            return "[{$p->type}] ({$p->scope}): {$p->content}";
        })->implode("\n\n");

        $systemPrompt = <<<PROMPT
Eres el agente Policy & Brand de MDC21 Agency. Tu trabajo es validar contenido contra las políticas definidas.

POLÍTICAS ACTIVAS:
{$policyText}

INSTRUCCIONES:
1. Analiza el contenido contra CADA política
2. Identifica violaciones específicas con la política que violan
3. Decide: "approved" (cumple todas) o "rejected" (viola alguna)
4. Responde SOLO en JSON válido con este formato exacto:

{"decision":"approved|rejected","reason":"explicación breve","violations":[{"policy_type":"tipo","violation":"descripción"}]}
PROMPT;

        try {
            $claude = app(ClaudeService::class);
            $model = config('services.claude.model_policy', 'claude-haiku-4-5');

            $response = $claude->message(
                prompt: "Analiza este contenido ({$this->contentType}):\n\n{$this->content}",
                model: $model,
                maxTokens: 512,
                systemPrompt: [$systemPrompt],
                useCache: true
            );

            $text = $claude->extractText($response);
            $parsed = json_decode($text, true);

            if (! $parsed || ! isset($parsed['decision'])) {
                Log::warning('[policy_brand] Failed to parse AI response, falling back to rules', ['text' => $text]);

                return $this->validateWithRules($policies);
            }

            return [
                'decision' => $parsed['decision'],
                'reason' => $parsed['reason'] ?? 'AI evaluation completed',
                'violations' => $parsed['violations'] ?? [],
                'method' => 'ai_claude_haiku',
                'policies_checked' => $policies->count(),
                'model' => $model,
                'usage' => $response['usage'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('[policy_brand] AI validation failed, falling back to rules', [
                'error' => $e->getMessage(),
            ]);

            return $this->validateWithRules($policies);
        }
    }

    private function validateWithRules(Collection $policies): array
    {
        $violations = [];
        $content = mb_strtolower($this->content);

        foreach ($policies as $policy) {
            $rules = $this->extractRulesFromPolicy($policy);

            foreach ($rules as $rule) {
                if ($this->checkRuleViolation($content, $rule)) {
                    $violations[] = [
                        'policy_type' => $policy->type,
                        'policy_scope' => $policy->scope,
                        'violation' => $rule['description'],
                    ];
                }
            }
        }

        $decision = empty($violations) ? 'approved' : 'rejected';
        $reason = empty($violations)
            ? 'Content passes all policy rules'
            : 'Found '.count($violations).' violation(s): '.$violations[0]['violation'];

        return [
            'decision' => $decision,
            'reason' => $reason,
            'violations' => $violations,
            'method' => 'rule_based',
            'policies_checked' => $policies->count(),
        ];
    }

    private function extractRulesFromPolicy(Policy $policy): array
    {
        $rules = [];

        // Tone rules
        if ($policy->type === 'tone') {
            $rules[] = ['type' => 'forbidden_words', 'words' => ['el mejor', 'único', 'garantizado', 'el más barato', 'sin riesgo'], 'description' => 'Superlativos prohibidos encontrados'];
        }

        // Legal rules
        if ($policy->type === 'legal') {
            $rules[] = ['type' => 'forbidden_words', 'words' => ['garantizamos', 'te aseguramos', 'rentabilidad asegurada', 'beneficio seguro'], 'description' => 'Promesas de resultados no permitidas'];
        }

        // Brand rules
        if ($policy->type === 'brand') {
            $rules[] = ['type' => 'forbidden_words', 'words' => ['bankinter', 'caixabank', 'bbva', 'santander', 'sabadell'], 'description' => 'Mención de competidores por nombre'];
        }

        // Privacy rules
        if ($policy->type === 'privacy') {
            $rules[] = ['type' => 'pattern', 'pattern' => '/\b\d{8}[a-z]\b/i', 'description' => 'Posible DNI/NIE detectado en el contenido'];
            $rules[] = ['type' => 'pattern', 'pattern' => '/\b[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}\b/i', 'description' => 'Email personal detectado en el contenido'];
        }

        // SEO rules
        if ($policy->type === 'seo' && $this->contentType === 'article') {
            $rules[] = ['type' => 'min_length', 'min' => 800, 'description' => 'Artículo con menos de 800 palabras'];
        }

        return $rules;
    }

    private function checkRuleViolation(string $content, array $rule): bool
    {
        return match ($rule['type']) {
            'forbidden_words' => $this->containsForbiddenWords($content, $rule['words']),
            'pattern' => (bool) preg_match($rule['pattern'], $content),
            'min_length' => str_word_count($content) < $rule['min'],
            default => false,
        };
    }

    private function containsForbiddenWords(string $content, array $words): bool
    {
        foreach ($words as $word) {
            if (str_contains($content, mb_strtolower($word))) {
                return true;
            }
        }

        return false;
    }
}
