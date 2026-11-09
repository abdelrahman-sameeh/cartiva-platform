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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'currency' => env('STRIPE_CURRENCY', 'egp'),
        'checkout' => [
            'webhook_secret' => env('STRIPE_CHECKOUT_WEBHOOK_SECRET'),
        ],
        'connect' => [
            'country' => env('STRIPE_CONNECT_COUNTRY'),
            'webhook_secret' => env('STRIPE_CONNECT_WEBHOOK_SECRET'),
        ],
        'fees' => [
            'platform_commission_bps' => (int) env('PLATFORM_COMMISSION_BPS', 500),
            'estimated_processing_bps' => (int) env('STRIPE_ESTIMATED_PROCESSING_BPS', 290),
            'estimated_currency_conversion_bps' => (int) env('STRIPE_ESTIMATED_CURRENCY_CONVERSION_BPS', 100),
            'estimated_international_bps' => (int) env('STRIPE_ESTIMATED_INTERNATIONAL_BPS', 150),
            'estimated_fixed_usd_minor' => (int) env('STRIPE_ESTIMATED_FIXED_USD_MINOR', 30),
            'estimated_usd_egp_rate' => (float) env('STRIPE_ESTIMATED_USD_EGP_RATE', 56),
        ],
    ],

    'demo_shipping' => [
        'platform_commission_bps' => (int) env('PLATFORM_COMMISSION_BPS', 500),
        'shipping_fee' => (float) env('DEMO_SHIPPING_FEE', 0),
        'cod_collection_bps' => (int) env('DEMO_COD_COLLECTION_BPS', 100),
    ],

];
