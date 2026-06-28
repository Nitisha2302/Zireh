<?php

return [
    'otp' => [
        'expires_minutes' => (int) env('CUSTOMER_OTP_EXPIRES_MINUTES', 5),
        'registration_completion_minutes' => (int) env('CUSTOMER_REGISTRATION_COMPLETION_MINUTES', 30),
        'max_verify_attempts' => (int) env('CUSTOMER_OTP_MAX_VERIFY_ATTEMPTS', 5),
        'resend_cooldown_seconds' => (int) env('CUSTOMER_OTP_RESEND_COOLDOWN_SECONDS', 60),
        'max_resends_per_hour' => (int) env('CUSTOMER_OTP_MAX_RESENDS_PER_HOUR', 5),
    ],
];
