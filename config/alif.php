<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Alif Provider Credentials
    |--------------------------------------------------------------------------
    |
    | Alif authenticates every provider request with an Authorization header
    | holding BASE64("Login:Password"). Admin Panel settings override these
    | env fallbacks. Never log or expose the password.
    |
    */

    'login' => env('ALIF_LOGIN'),

    'password' => env('ALIF_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Accepted Payment Amounts
    |--------------------------------------------------------------------------
    |
    | Amounts are handled as decimal strings, never floats. A pay request with
    | an amount outside this inclusive range is answered with code 405.
    |
    */

    'min_amount' => env('ALIF_MIN_AMOUNT', '1.00'),

    'max_amount' => env('ALIF_MAX_AMOUNT', '20000.00'),

    'currency' => env('ALIF_CURRENCY', 'TJS'),

    /*
    |--------------------------------------------------------------------------
    | Service Identifier
    |--------------------------------------------------------------------------
    |
    | Optional. When set, a pay or check request carrying a different srv_id is
    | rejected. Leave empty to accept any srv_id.
    |
    */

    'srv_id' => env('ALIF_SRV_ID'),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Channel used for Alif diagnostics, plus how long request logs are kept
    | when an admin purges them from the panel.
    |
    */

    'log_channel' => env('ALIF_LOG_CHANNEL', 'alif'),

    'log_retention_days' => (int) env('ALIF_LOG_RETENTION_DAYS', 30),

];
