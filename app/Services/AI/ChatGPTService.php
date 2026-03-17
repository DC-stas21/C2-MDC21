<?php

namespace App\Services\AI;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatGPTService
{
    private string $apiKey;

    private string $baseUrl = 'https://api.openai.com/v1';

    private RateLimiterService $rateLimiter;

    public function __construct(RateLimiterService $rateLimiter)
    {
        $this->apiKey = config('services.openai.api_key', '');
        $this->rateLimiter = $rateLimiter;
    }

    public function message(
        string $prompt,
        ?string $model = null,
        int $maxTokens = 2048,
        string $systemPrompt = ''
    ): array {
        $model ??= config('services.openai.model_research', 'gpt-4o');

        return $this->rateLimiter->attempt('openai', function () use ($prompt, $model, $maxTokens, $systemPrompt) {
            $messages = [];

            if ($systemPrompt) {
                $messages[] = ['role' => 'system', 'content' => $systemPrompt];
            }

            $messages[] = ['role' => 'user', 'content' => $prompt];

            $response = Http::withToken($this->apiKey)
                ->post("{$this->baseUrl}/chat/completions", [
                    'model' => $model,
                    'max_tokens' => $maxTokens,
                    'messages' => $messages,
                ]);

            $this->handleErrors($response, 'OpenAI');

            return $response->json();
        });
    }

    public function batch(array $requests, ?string $model = null): array
    {
        $model ??= config('services.openai.model_content', 'gpt-4o-mini');

        return $this->rateLimiter->attempt('openai-batch', function () use ($requests, $model) {
            $lines = [];
            foreach ($requests as $id => $prompt) {
                $lines[] = json_encode([
                    'custom_id' => (string) $id,
                    'method' => 'POST',
                    'url' => '/v1/chat/completions',
                    'body' => [
                        'model' => $model,
                        'max_tokens' => 2048,
                        'messages' => [['role' => 'user', 'content' => $prompt]],
                    ],
                ]);
            }

            $fileResponse = Http::withToken($this->apiKey)
                ->attach('file', implode("\n", $lines), 'batch.jsonl', ['Content-Type' => 'application/jsonl'])
                ->post("{$this->baseUrl}/files", ['purpose' => 'batch']);

            $this->handleErrors($fileResponse, 'OpenAI Files');

            $fileId = $fileResponse->json('id');

            $batchResponse = Http::withToken($this->apiKey)
                ->post("{$this->baseUrl}/batches", [
                    'input_file_id' => $fileId,
                    'endpoint' => '/v1/chat/completions',
                    'completion_window' => '24h',
                ]);

            $this->handleErrors($batchResponse, 'OpenAI Batch');

            return $batchResponse->json();
        });
    }

    public function getBatchStatus(string $batchId): array
    {
        $response = Http::withToken($this->apiKey)
            ->get("{$this->baseUrl}/batches/{$batchId}");

        $this->handleErrors($response, 'OpenAI Batch Status');

        return $response->json();
    }

    public function extractText(array $response): string
    {
        return $response['choices'][0]['message']['content'] ?? '';
    }

    private function handleErrors(Response $response, string $service): void
    {
        if ($response->failed()) {
            Log::error("{$service} API error", [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new \RuntimeException("{$service} API error: {$response->status()}");
        }
    }
}
