<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'claude' => [
        'api_key' => env('CLAUDE_API_KEY'),
        'model_orchestrator' => env('CLAUDE_MODEL_ORCHESTRATOR', 'claude-sonnet-4-5'),
        'model_policy' => env('CLAUDE_MODEL_POLICY', 'claude-haiku-4-5'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model_research' => env('OPENAI_MODEL_RESEARCH', 'gpt-4o'),
        'model_content' => env('OPENAI_MODEL_CONTENT', 'gpt-4o-mini'),
    ],

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'groups' => [
            'infra' => env('TELEGRAM_GROUP_INFRA'),
            'content' => env('TELEGRAM_GROUP_CONTENT'),
            'negocio' => env('TELEGRAM_GROUP_NEGOCIO'),
        ],
    ],

    'adsense' => [
        'client_id' => env('ADSENSE_CLIENT_ID', ''),
    ],

    'cloudflare' => [
        'api_token' => env('CLOUDFLARE_API_TOKEN'),
    ],

    'github' => [
        'token' => env('GITHUB_TOKEN'),
    ],

];
