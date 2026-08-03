<?php

return [
    'super_admin' => [
        'name' => env('CRM_SUPER_ADMIN_NAME', 'Main Admin'),
        'phone' => env('CRM_SUPER_ADMIN_PHONE', '8233365888'),
        'email' => env('CRM_SUPER_ADMIN_EMAIL', 'Admissions@onedegreeadvisory.com'),
    ],
    'additional_super_admins' => [
        [
            'name' => env('CRM_SECOND_SUPER_ADMIN_NAME', 'Harshit Totuka'),
            'phone' => env('CRM_SECOND_SUPER_ADMIN_PHONE', '9829027413'),
            'email' => env('CRM_SECOND_SUPER_ADMIN_EMAIL', 'harshittotuka1@gmail.com'),
        ],
    ],
    'otp' => [
        'ttl_minutes' => (int) env('CRM_OTP_TTL_MINUTES', 5),
        'max_attempts' => (int) env('CRM_OTP_MAX_ATTEMPTS', 5),
        'debug' => (bool) env('CRM_OTP_DEBUG', false),
        'channels' => array_values(array_filter(array_map('trim', explode(',', (string) env('CRM_OTP_CHANNELS', 'email'))))),

        /* Standing master OTP. The listed accounts can sign in with this fixed
           code at any time, on any environment, instead of waiting for the
           emailed one — the escape hatch for when mail delivery is down. It
           never expires and is not rate-limited, so it is deliberately kept OUT
           of the repo: both values come from the environment only, and leaving
           CRM_MASTER_OTP empty switches the bypass off entirely. */
        'master' => [
            'code' => trim((string) env('CRM_MASTER_OTP', '')),
            'emails' => array_values(array_filter(array_map(
                static fn (string $email): string => strtolower(trim($email)),
                explode(',', (string) env('CRM_MASTER_OTP_EMAILS', ''))
            ))),
        ],
    ],
    'sms' => [
        'driver' => env('CRM_SMS_DRIVER', 'msg91'),
        'webhook_url' => env('CRM_SMS_WEBHOOK_URL'),
        'bearer_token' => env('CRM_SMS_BEARER_TOKEN'),
        'msg91' => [
            'endpoint' => env('MSG91_ENDPOINT', 'https://control.msg91.com/api/v5/flow'),
            'auth_key' => env('MSG91_AUTH_KEY'),
            'flow_id' => env('MSG91_FLOW_ID'),
            'otp_variable' => env('MSG91_OTP_VARIABLE', 'OTP'),
        ],
    ],
    /* The CRM sends from its own mailbox so the public contact/careers forms
       are untouched. If CRM_MAIL_* is not set the sender falls back to the
       contact_form mailer and its from address. */
    'email' => [
        'enabled' => (bool) env('CRM_EMAIL_NOTIFICATIONS', true),
        'mailer' => env('CRM_MAILER', 'contact_form'),
        'from' => env('CRM_MAIL_FROM', env('CRM_MAIL_USERNAME')),
        'from_name' => env('CRM_MAIL_FROM_NAME', 'One Degree CRM'),
    ],
];
