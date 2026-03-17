<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\RateLimiter;

class RateLimiterService
{
    public function attempt(string $provider, callable $callback, int $maxAttempts = 60, int $decaySeconds = 60): mixed
    {
        $key = "ai-api:{$provider}";

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            throw new \RuntimeException("Rate limit exceeded for {$provider}. Retry in {$seconds}s.");
        }

        RateLimiter::hit($key, $decaySeconds);

        return $callback();
    }

    public function availableIn(string $provider): int
    {
        return RateLimiter::availableIn("ai-api:{$provider}");
    }

    public function clear(string $provider): void
    {
        RateLimiter::clear("ai-api:{$provider}");
    }
}
