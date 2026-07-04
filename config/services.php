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
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

    'apple' => [
        'client_id' => env('APPLE_CLIENT_ID'),
        'client_secret' => env('APPLE_CLIENT_SECRET'),
        'key_id' => env('APPLE_KEY_ID'),
        'team_id' => env('APPLE_TEAM_ID'),
        'private_key' => env('APPLE_PRIVATE_KEY'),
        'passphrase' => env('APPLE_PASSPHRASE'),
        'signer' => env('APPLE_SIGNER'),
        'redirect' => env('APPLE_REDIRECT_URI'),
        'jwt_issued_time_leeway' => env('APPLE_JWT_ISSUED_TIME_LEEWAY'),
    ],

    'didit' => [
        'base_url' => env('DIDIT_BASE_URL', 'https://verification.didit.me'),
        'api_key' => env('DIDIT_API_KEY'),
        'workflow_id' => env('DIDIT_WORKFLOW_ID'),
        'callback_url' => env('DIDIT_CALLBACK_URL'),
    ],

    'osonsms' => [
        'base_url' => env('OSONSMS_BASE_URL', 'https://api.osonsms.com'),
        'login' => env('OSONSMS_LOGIN'),
        'token' => env('OSONSMS_TOKEN'),
        'sender' => env('OSONSMS_SENDER'),
    ],

    'elim' => [
        'base_url' => env('ELIM_BASE_URL', 'https://openapi.elim.asia'),
        'email' => env('ELIM_EMAIL'),
        'password' => env('ELIM_PASSWORD'),
        'timeout' => env('ELIM_TIMEOUT', 20),
        'retries' => env('ELIM_RETRIES', 2),
        'retry_sleep' => env('ELIM_RETRY_SLEEP', 300),
        'token_ttl' => env('ELIM_TOKEN_TTL', 3300),
        'default_lang' => env('ELIM_DEFAULT_LANG', 'en'),
        'default_query' => env('ELIM_DEFAULT_QUERY', 'bag'),
        'cache' => [
            'products_ttl' => env('ELIM_PRODUCTS_CACHE_TTL', 900),
            'categories_ttl' => env('ELIM_CATEGORIES_CACHE_TTL', 86400),
        ],
    ],

    'exchange_rate' => [
        'api_url' => env('EXCHANGE_RATE_API_URL', 'https://open.er-api.com/v6/latest/{from}'),
        'api_key' => env('EXCHANGE_RATE_API_KEY'),
        'timeout' => env('EXCHANGE_RATE_TIMEOUT', 15),
        'retries' => env('EXCHANGE_RATE_RETRIES', 2),
        'retry_sleep' => env('EXCHANGE_RATE_RETRY_SLEEP', 500),
        'default_rate' => env('EXCHANGE_RATE_DEFAULT', 1.5),
    ],

];
