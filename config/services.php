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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'site_verification' => env('GOOGLE_SITE_VERIFICATION'),
        'tag_id' => env('GOOGLE_TAG_ID', 'G-R93ML7ZB8K'),
        'tag_manager_id' => env('GOOGLE_TAG_MANAGER_ID'),
    ],

    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        'api_url' => env('RAZORPAY_API_URL', 'https://api.razorpay.com/v1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Visa mock-interview AI
    |--------------------------------------------------------------------------
    |
    | Answers are assessed by an ordered fallback chain:
    |   1. Groq  llama-3.3-70b-versatile  (fast, best quality)
    |   2. Groq  llama-3.1-8b-instant     (absorbs 70B rate-limit bursts)
    |   3. Local Ollama qwen2.5:1.5b      (always-available, private fallback)
    |
    | The Groq tiers are skipped entirely when GROQ_API_KEY is empty, so with no
    | key the service is local-only (as it was before). A report is generated
    | only after a tier returns a valid structured assessment.
    |
    */
    'visa_mock_ai' => [
        'enabled' => env('VISA_MOCK_AI_ENABLED', true),

        // Remote Groq tiers (OpenAI-compatible). Skipped when no key is set.
        'groq' => [
            'key' => env('GROQ_API_KEY'),
            'url' => env('GROQ_API_URL', 'https://api.groq.com/openai/v1'),
            'primary_model' => env('VISA_MOCK_GROQ_PRIMARY_MODEL', 'llama-3.3-70b-versatile'),
            'fallback_model' => env('VISA_MOCK_GROQ_FALLBACK_MODEL', 'llama-3.1-8b-instant'),
            'connect_timeout' => (float) env('VISA_MOCK_GROQ_CONNECT_TIMEOUT', 5),
            'timeout' => (float) env('VISA_MOCK_GROQ_TIMEOUT', 45),
        ],

        // Local Ollama tier (final fallback; the only tier used in tests/dev
        // without a Groq key). Default model matches what is installed on the VPS.
        'url' => env('VISA_MOCK_AI_URL', 'http://127.0.0.1:11434'),
        'model' => env('VISA_MOCK_AI_MODEL', 'qwen2.5:1.5b'),
        'connect_timeout' => (float) env('VISA_MOCK_AI_CONNECT_TIMEOUT', 1.5),
        'timeout' => (float) env('VISA_MOCK_AI_TIMEOUT', 300),
        'keep_alive' => env('VISA_MOCK_AI_KEEP_ALIVE', '30m'),
        'num_ctx' => (int) env('VISA_MOCK_AI_NUM_CTX', 2048),
        'num_predict' => (int) env('VISA_MOCK_AI_NUM_PREDICT', 700),
        'batch_timeout' => (float) env('VISA_MOCK_AI_BATCH_TIMEOUT', 600),
        'batch_num_ctx' => (int) env('VISA_MOCK_AI_BATCH_NUM_CTX', 4096),
        'batch_num_predict' => (int) env('VISA_MOCK_AI_BATCH_NUM_PREDICT', 2400),
    ],

];
