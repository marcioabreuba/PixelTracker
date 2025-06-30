<?php

return [
    'domains' => [
        'test123' => [
            'pixel_id' => env('FACEBOOK_PIXEL_ID', ''),
            'access_token' => env('FACEBOOK_ACCESS_TOKEN', ''),
            'test_code' => env('FACEBOOK_TEST_CODE', 'TEST57660'),
        ],
        'shopify_store' => [
            'pixel_id' => env('FACEBOOK_PIXEL_ID', ''),
            'access_token' => env('FACEBOOK_ACCESS_TOKEN', ''),
            'test_code' => env('FACEBOOK_TEST_CODE', 'TEST57660'),
        ],
        // Adicione mais domínios conforme necessário
    ],
];
