<?php

namespace App\Providers;

use App\Services\AI\ChatGPTService;
use App\Services\AI\ClaudeService;
use App\Services\AI\RateLimiterService;
use App\Services\PromptRegistry;
use App\Services\ScoreComposite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RateLimiterService::class);

        $this->app->singleton(ClaudeService::class, fn ($app) => new ClaudeService($app->make(RateLimiterService::class)));

        $this->app->singleton(ChatGPTService::class, fn ($app) => new ChatGPTService($app->make(RateLimiterService::class)));

        $this->app->singleton(PromptRegistry::class);

        $this->app->singleton(ScoreComposite::class);
    }

    public function boot(): void
    {
        //
    }
}
