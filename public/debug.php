<?php
// Teste PHP puro - sem Laravel
header('Content-Type: application/json');
echo json_encode([
    'status' => 'PHP_WORKING',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => phpversion(),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
    'environment_vars' => [
        'APP_KEY' => $_ENV['APP_KEY'] ?? 'NOT_SET',
        'APP_ENV' => $_ENV['APP_ENV'] ?? 'NOT_SET',
        'DB_CONNECTION' => $_ENV['DB_CONNECTION'] ?? 'NOT_SET',
        'PORT' => $_ENV['PORT'] ?? 'NOT_SET'
    ]
]); 