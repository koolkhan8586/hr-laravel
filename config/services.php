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

    /*
    |--------------------------------------------------------------------------
    | WAHA (WhatsApp HTTP API)
    |--------------------------------------------------------------------------
    |
    | Used to send attendance reminders via WhatsApp.
    | Docs: https://waha.devlike.pro/docs/how-to/send-messages/
    |
    */
    'waha' => [
        'enabled' => env('WAHA_ENABLED', false),
        'base_url' => env('WAHA_BASE_URL'),
        'api_key' => env('WAHA_API_KEY'),
        'session' => env('WAHA_SESSION', 'default'),
        'default_country_code' => env('WAHA_DEFAULT_COUNTRY_CODE', '92'),
        'timeout' => env('WAHA_TIMEOUT', 20),
    ],

];
