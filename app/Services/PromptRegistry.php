<?php

namespace App\Services;

use App\Models\PromptVersion;
use Illuminate\Support\Facades\Cache;

class PromptRegistry
{
    private const CACHE_TTL = 300;

    public function get(string $agentType): ?PromptVersion
    {
        $cacheKey = "prompt_registry:{$agentType}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($agentType) {
            return PromptVersion::where('agent_type', $agentType)
                ->where('is_active', true)
                ->latest('version')
                ->first();
        });
    }

    public function getPromptText(string $agentType, string $fallback = ''): string
    {
        return $this->get($agentType)?->prompt_text ?? $fallback;
    }

    public function getModel(string $agentType, string $fallback = 'claude-sonnet-4-5'): string
    {
        return $this->get($agentType)?->model ?? $fallback;
    }

    public function recordMetrics(string $agentType, array $metrics): void
    {
        $prompt = PromptVersion::where('agent_type', $agentType)
            ->where('is_active', true)
            ->latest('version')
            ->first();

        if ($prompt) {
            $existing = $prompt->metrics ?? [];
            $prompt->update(['metrics' => array_merge($existing, $metrics)]);
            Cache::forget("prompt_registry:{$agentType}");
        }
    }

    public function invalidate(string $agentType): void
    {
        Cache::forget("prompt_registry:{$agentType}");
    }
}
