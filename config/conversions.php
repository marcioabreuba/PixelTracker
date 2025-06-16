<?php

return [
    'domains' => [
        'test123' => [
            'pixel_id' => env('FACEBOOK_PIXEL_ID', '676999668497170'),
            'access_token' => env('FACEBOOK_ACCESS_TOKEN', ''),
            'test_code' => env('FACEBOOK_TEST_CODE', 'TEST57660'),
        ],
        'shopify_store' => [
            'pixel_id' => env('FACEBOOK_PIXEL_ID', '676999668497170'),
            'access_token' => env('FACEBOOK_ACCESS_TOKEN', 'EAAIBXRkrXWABOZBjTuLX2PyLCk5ylRLMiwALtlCOKLMSH2t6EDFhv4fTC74ieLloMnC2ozZAXmRiYKqBOSSgoOyGMmOKYdl5HpNBmAVQrKxLsBy3ZB9L69ZA42UQFyK7aJZBGUxa5duQf3ZBU5buKFpYUlZCswJmbcEGZAwxWWRA6wlQp0n8uh9iZBmgUjEjeluV0NQZDZD'),
            'test_code' => env('FACEBOOK_TEST_CODE', 'TEST57660'),
        ],
        // Adicione mais domínios conforme necessário
    ],
];
