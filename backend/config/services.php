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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'yandex' => [
        'api_key' => env('YANDEX_GPT_API_KEY'),
        'folder_id' => env('YANDEX_GPT_FOLDER_ID'),
    ],

    'gigachat' => [
        'auth_key' => env('GIGACHAT_AUTH_KEY'),
        'scope'    => env('GIGACHAT_SCOPE'),
    ],

    'openrouter' => [
        'api_key' => env('OPENROUTER_API_KEY'),
        'default_model' => env('OPENROUTER_DEFAULT_MODEL', 'qwen/qwen3-coder:free'),
        'models' => [
            'qwen/qwen3-coder:free' => 'Qwen3 Coder (Free)',
            'meta-llama/llama-3.2-3b-instruct:free' => 'Llama 3.2 3B (Free)',
            'microsoft/phi-3-mini-128k-instruct:free' => 'Phi-3 Mini (Free)',
            'google/gemma-2-9b-it:free' => 'Gemma 2 9B (Free)',
            'mistralai/mistral-7b-instruct:free' => 'Mistral 7B (Free)',
            'huggingfaceh4/zephyr-7b-beta:free' => 'Zephyr 7B Beta (Free)',
            'openchat/openchat-7b:free' => 'OpenChat 7B (Free)',
            'gryphe/mythomist-7b:free' => 'MythoMist 7B (Free)',
            'undi95/toppy-m-7b:free' => 'Toppy M 7B (Free)',
            'openrouter/auto' => 'Auto (Best Available)',
        ],
    ],

];
