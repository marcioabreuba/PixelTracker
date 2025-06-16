<?php
// Debug dos logs do Laravel
header('Content-Type: application/json');

$logPath = '/var/www/html/storage/logs/laravel.log';
$response = [
    'log_exists' => file_exists($logPath),
    'log_readable' => is_readable($logPath),
    'storage_writable' => is_writable('/var/www/html/storage'),
    'logs_writable' => is_writable('/var/www/html/storage/logs'),
];

if (file_exists($logPath) && is_readable($logPath)) {
    $logContent = file_get_contents($logPath);
    $lines = explode("\n", $logContent);
    $response['last_50_lines'] = array_slice($lines, -50);
    $response['log_size'] = filesize($logPath);
} else {
    $response['error'] = 'Log file not accessible';
}

// Verificar permissões
$response['permissions'] = [
    'storage' => substr(sprintf('%o', fileperms('/var/www/html/storage')), -4),
    'logs' => file_exists('/var/www/html/storage/logs') ? substr(sprintf('%o', fileperms('/var/www/html/storage/logs')), -4) : 'not_exists'
];

echo json_encode($response, JSON_PRETTY_PRINT); 