<?php

return [

    /*
    |--------------------------------------------------------------------------
    | PayHere Merchant Credentials
    |--------------------------------------------------------------------------
    |
    | Never expose merchant_secret to the frontend. Hashes are generated
    | server-side only. Sandbox credentials are for testing only.
    |
    */

    'merchant_id' => env('PAYHERE_MERCHANT_ID', ''),

    'merchant_secret' => env('PAYHERE_MERCHANT_SECRET', ''),

    'sandbox' => filter_var(env('PAYHERE_SANDBOX', true), FILTER_VALIDATE_BOOLEAN),

    'currency' => env('PAYHERE_CURRENCY', 'LKR'),

    /*
    |--------------------------------------------------------------------------
    | Callback / Redirect URLs
    |--------------------------------------------------------------------------
    |
    | notify_url MUST be publicly reachable (use ngrok/Cloudflare Tunnel in local).
    | PayHere will not deliver notifications to localhost.
    |
    */

    'notify_url' => env('PAYHERE_NOTIFY_URL', env('APP_URL', 'http://localhost:8000').'/api/payments/payhere/notify'),

    'frontend_url' => rtrim(env('FRONTEND_URL', 'http://localhost:3000'), '/'),

    'reservation_minutes' => (int) env('PAYHERE_RESERVATION_MINUTES', 15),

    /*
    | When true AND sandbox mode, customer return/polling can finalize a pending
    | booking if PayHere IPN cannot reach localhost. Never enable in production.
    */
    'trust_return_in_sandbox' => filter_var(env('PAYHERE_TRUST_RETURN_IN_SANDBOX', false), FILTER_VALIDATE_BOOLEAN),

    /*
    | Optional Retrieval API credentials (Settings → API Keys in PayHere).
    | When set, return/poll will verify payment with PayHere before confirming.
    */
    'app_id' => env('PAYHERE_APP_ID', ''),

    'app_secret' => env('PAYHERE_APP_SECRET', ''),

    'checkout_url' => [
        'sandbox' => 'https://sandbox.payhere.lk/pay/checkout',
        'live' => 'https://www.payhere.lk/pay/checkout',
    ],

    'oauth_token_url' => [
        'sandbox' => 'https://sandbox.payhere.lk/merchant/v1/oauth/token',
        'live' => 'https://www.payhere.lk/merchant/v1/oauth/token',
    ],

    'retrieval_url' => [
        'sandbox' => 'https://sandbox.payhere.lk/merchant/v1/payment/search',
        'live' => 'https://www.payhere.lk/merchant/v1/payment/search',
    ],

];
