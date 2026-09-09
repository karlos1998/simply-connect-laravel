<?php

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
];
