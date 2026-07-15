<?php

return [
    'super_admin' => [
        'name' => env('CRM_SUPER_ADMIN_NAME', 'Main Admin'),
        'phone' => env('CRM_SUPER_ADMIN_PHONE', '8233365888'),
    ],
    'otp' => [
        'ttl_minutes' => (int) env('CRM_OTP_TTL_MINUTES', 5),
        'max_attempts' => (int) env('CRM_OTP_MAX_ATTEMPTS', 5),
        'debug' => (bool) env('CRM_OTP_DEBUG', false),
    ],
    'sms' => [
        'webhook_url' => env('CRM_SMS_WEBHOOK_URL'),
        'bearer_token' => env('CRM_SMS_BEARER_TOKEN'),
    ],
];
