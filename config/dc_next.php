<?php

return [
    'merchant' => env('DC_NEXT_MERCHANT', ''),
    'secret' => env('DC_NEXT_SECRET', ''),
    'callback_url' => env('DC_NEXT_CALLBACK_URL', ''),
    'create_url' => env('DC_NEXT_CREATE_URL', 'https://invoice.dc.tj/v1/create'),
    'check_url' => env('DC_NEXT_CHECK_URL', 'https://invoice.dc.tj/v1/check'),
    'default_currency' => env('DC_NEXT_CURRENCY', 'TJS'),
    'invoice_exp_minutes' => (int) env('DC_NEXT_EXP_MINUTES', 40),
    'min_amount' => env('DC_NEXT_MIN_AMOUNT', '1.00'),
    'max_amount' => env('DC_NEXT_MAX_AMOUNT', '20000.00'),
    'timeout' => (int) env('DC_NEXT_TIMEOUT', 30),
];
