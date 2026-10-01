<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Alif Acquiring
    |--------------------------------------------------------------------------
    |
    | The mobile app inits a top-up; we call Alif's hosted checkout. Admin
    | Panel settings override these env fallbacks. Never log the password.
    |
    | Test:  https://test-web.alif.tj
    | Live:  https://web.alif.tj
    |
    */

    'terminal_key' => env('ALIF_TERMINAL_KEY', ''),

    'terminal_password' => env('ALIF_TERMINAL_PASSWORD', ''),

    'base_url' => rtrim(env('ALIF_BASE_URL', 'https://test-web.alif.tj'), '/'),

    // korti_milli = full hosted checkout (Mobi, Visa, MC). wallet = Alif Mobi only.
    'gate' => env('ALIF_GATE', 'korti_milli'),

    'callback_url' => env('ALIF_CALLBACK_URL', ''),

    'return_url' => env('ALIF_RETURN_URL', ''),

    'timeout' => (int) env('ALIF_TIMEOUT', 30),

    'min_amount' => env('ALIF_MIN_AMOUNT', '1.00'),

    'max_amount' => env('ALIF_MAX_AMOUNT', '20000.00'),

    'currency' => env('ALIF_CURRENCY', 'TJS'),

    'log_channel' => env('ALIF_LOG_CHANNEL', 'alif'),

    'log_retention_days' => (int) env('ALIF_LOG_RETENTION_DAYS', 30),

];
