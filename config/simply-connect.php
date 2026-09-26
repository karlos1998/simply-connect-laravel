<?php

use SimplyConnect\Laravel\Http\Middleware\Authorize;

return [
    'default' => env('SIMPLY_CONNECT_CONNECTION', 'default'),

    'connections' => [
        'default' => [
            'base_url' => env('SIMPLY_CONNECT_BASE_URL', 'https://api.simply-connect.ovh'),
            'api_key' => env('SIMPLY_CONNECT_API_KEY'),
            'default_endpoint' => env('SIMPLY_CONNECT_SMS_ENDPOINT_ID'),
            'endpoints' => [
                // 'customer-service' => env('SIMPLY_CONNECT_CUSTOMER_SERVICE_ENDPOINT_ID'),
            ],
            'timeout' => (int) env('SIMPLY_CONNECT_TIMEOUT', 10),
            'connect_timeout' => (int) env('SIMPLY_CONNECT_CONNECT_TIMEOUT', 3),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Developer panel
    |--------------------------------------------------------------------------
    |
    | The panel is opt-in. Its authorization follows the same model as Laravel
    | Telescope: local is open, while other environments use the
    | "viewSimplyConnect" gate from the published application provider.
    |
    */
    'panel' => [
        'enabled' => env('SIMPLY_CONNECT_PANEL_ENABLED', false),
        'domain' => env('SIMPLY_CONNECT_PANEL_DOMAIN'),
        'path' => env('SIMPLY_CONNECT_PANEL_PATH', 'simply-connect'),
        'connection' => env('SIMPLY_CONNECT_PANEL_CONNECTION'),
        'middleware' => [
            'web',
            Authorize::class,
        ],
    ],
];
