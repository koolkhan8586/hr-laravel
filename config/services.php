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
        // Comma-separated mobiles for the 11:38 daily Absent/Late/Leave report
        'daily_report_mobiles' => env('WAHA_DAILY_REPORT_MOBILES', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cafe LSAF (cafe.khanmusa.com)
    |--------------------------------------------------------------------------
    |
    | Employee Panel → Cafe opens a short-lived SSO link into the cafe app.
    | CAFE_SSO_SECRET must match Cafe's HR_SSO_SECRET (min 16 characters).
    |
    */
    'cafe' => [
        'base_url' => env('CAFE_BASE_URL', 'https://cafe.khanmusa.com'),
        'sso_secret' => env('CAFE_SSO_SECRET'),
        'sso_ttl' => env('CAFE_SSO_TTL', 120),
    ],

];
