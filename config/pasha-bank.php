<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Merchant Handler URL
    |--------------------------------------------------------------------------
    | Server-to-server API endpoint for all payment commands.
    */
    'merchant_handler' => env('PASHA_BANK_MERCHANT_HANDLER', 'https://ecomm.pashabank.az:18443/ecomm2/MerchantHandler'),

    /*
    |--------------------------------------------------------------------------
    | Client Handler URL
    |--------------------------------------------------------------------------
    | Client browser redirect URL for card data entry.
    */
    'client_handler' => env('PASHA_BANK_CLIENT_HANDLER', 'https://ecomm.pashabank.az:8463/ecomm2/ClientHandler'),

    /*
    |--------------------------------------------------------------------------
    | Terminal ID
    |--------------------------------------------------------------------------
    | Terminal identifier provided by the bank.
    */
    'terminal_id' => env('PASHA_BANK_TERMINAL_ID'),

    /*
    |--------------------------------------------------------------------------
    | SSL Certificate Configuration
    |--------------------------------------------------------------------------
    | Pasha Bank uses mutual TLS with client certificates.
    | You can provide either a .p12 (PKCS#12) file or separate .pem files.
    */
    'certificate' => env('PASHA_BANK_CERTIFICATE'),
    'certificate_password' => env('PASHA_BANK_CERTIFICATE_PASSWORD'),
    'private_key' => env('PASHA_BANK_PRIVATE_KEY'),
    'private_key_password' => env('PASHA_BANK_PRIVATE_KEY_PASSWORD'),
    'ca_certificate' => env('PASHA_BANK_CA_CERTIFICATE'),

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    | ISO-4217 numeric currency code. 944=AZN, 840=USD, 978=EUR
    */
    'currency' => env('PASHA_BANK_CURRENCY', '944'),

    /*
    |--------------------------------------------------------------------------
    | Default Language
    |--------------------------------------------------------------------------
    | Language for the bank's card entry page: az, en, ru
    */
    'language' => env('PASHA_BANK_LANGUAGE', 'az'),

    /*
    |--------------------------------------------------------------------------
    | Return URLs
    |--------------------------------------------------------------------------
    | URLs where the client is redirected after payment completion.
    | These are displayed to the user after command=c result is processed.
    */
    'success_url' => env('PASHA_BANK_SUCCESS_URL'),
    'error_url' => env('PASHA_BANK_ERROR_URL'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Settings
    |--------------------------------------------------------------------------
    */
    'timeout' => env('PASHA_BANK_TIMEOUT', 30),
    'ssl_verify' => env('PASHA_BANK_SSL_VERIFY', true),

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'channel' => env('PASHA_BANK_LOG_CHANNEL', 'stack'),
        'level' => env('PASHA_BANK_LOG_LEVEL', 'info'),
    ],
];
