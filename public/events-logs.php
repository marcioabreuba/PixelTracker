<?php
// Debug dos logs do EventsController
header('Content-Type: application/json');

$logPath = '/var/www/html/storage/logs/laravel.log';
$response = [
    'log_exists' => file_exists($logPath),
    'log_readable' => is_readable($logPath),
];

if (file_exists($logPath) && is_readable($logPath)) {
    $logContent = file_get_contents($logPath);
    $lines = explode("\n", $logContent);
    
    // Filtrar apenas logs relacionados ao EventsController
    $eventLogs = [];
    foreach ($lines as $line) {
        if (strpos($line, 'DEBUG EVENTS CONTROLLER') !== false ||
            strpos($line, 'EventsController') !== false ||
            strpos($line, 'events/send') !== false ||
            strpos($line, 'ERROR') !== false) {
            $eventLogs[] = $line;
        }
    }
    
    $response['event_logs'] = array_slice($eventLogs, -20); // Últimos 20 logs relacionados
    $response['total_event_logs'] = count($eventLogs);
} else {
    $response['error'] = 'Log file not accessible';
}

echo json_encode($response, JSON_PRETTY_PRINT); 