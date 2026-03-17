<?php

namespace App\Services\AI;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ClaudeService
{
    private string $apiKey;

    private string $baseUrl = 'https://api.anthropic.com/v1';

    private RateLimiterService $rateLimiter;

    public function __construct(RateLimiterService $rateLimiter)
    {
        $this->apiKey = config('services.claude.api_key', '');
        $this->rateLimiter = $rateLimiter;
    }

    public function message(
        string $prompt,
        ?string $model = null,
        int $maxTokens = 2048,
        array $systemPrompt = [],
        bool $useCache = true
    ): array {
        $model ??= config('services.claude.model_orchestrator', 'claude-sonnet-4-5');

        return $this->rateLimiter->attempt('claude', function () use ($prompt, $model, $maxTokens, $systemPrompt, $useCache) {
            $messages = [['role' => 'user', 'content' => $prompt]];

            $system = [];
            foreach ($systemPrompt as $block) {
                $entry = ['type' => 'text', 'text' => $block];
                if ($useCache) {
                    $entry['cache_control'] = ['type' => 'ephemeral'];
                }
                $system[] = $entry;
            }

            $payload = [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'messages' => $messages,
            ];

            if (! empty($system)) {
                $payload['system'] = $system;
            }

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'anthropic-beta' => 'prompt-caching-2024-07-31',
                'content-type' => 'application/json',
            ])->post("{$this->baseUrl}/messages", $payload);

            $this->handleErrors($response, 'Claude');

            return $response->json();
        });
    }

    public function batchMessage(string $prompt, ?string $model = null, int $maxTokens = 4096): array
    {
        $model ??= config('services.claude.model_orchestrator', 'claude-sonnet-4-5');

        return $this->rateLimiter->attempt('claude-batch', function () use ($prompt, $model, $maxTokens) {
            $payload = [
                'requests' => [
                    [
                        'custom_id' => uniqid('req_'),
                        'params' => [
                            'model' => $model,
                            'max_tokens' => $maxTokens,
                            'messages' => [['role' => 'user', 'content' => $prompt]],
                        ],
                    ],
                ],
            ];

            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->post("{$this->baseUrl}/messages/batches", $payload);

            $this->handleErrors($response, 'Claude Batch');

            return $response->json();
        });
    }

    public function extractText(array $response): string
    {
        return $response['content'][0]['text'] ?? '';
    }

    private function handleErrors(Response $response, string $service): void
    {
        if ($response->failed()) {
            Log::error("{$service} API error", [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new \RuntimeException("{$service} API error: ".$response->status());
        }
    }
}
