<?php
// Visualização dos logs estruturados do PixelTracker
header('Content-Type: application/json');

$logPath = '/var/www/html/storage/logs/PixelTracker.log';
$response = [
    'log_exists' => file_exists($logPath),
    'log_readable' => is_readable($logPath),
    'timestamp' => date('Y-m-d H:i:s'),
];

if (file_exists($logPath) && is_readable($logPath)) {
    $logContent = file_get_contents($logPath);
    $lines = explode("\n", $logContent);
    
    // Pegar os últimos logs não vazios
    $recentLogs = [];
    $reversedLines = array_reverse($lines);
    
    foreach ($reversedLines as $line) {
        if (!empty(trim($line))) {
            $recentLogs[] = trim($line);
            if (count($recentLogs) >= 50) { // Últimos 50 logs
                break;
            }
        }
    }
    
    $response['recent_logs'] = array_reverse($recentLogs);
    $response['total_lines'] = count($lines);
    $response['log_size'] = filesize($logPath);
} else {
    $response['error'] = 'PixelTracker log file not accessible';
    $response['log_path'] = $logPath;
}

echo json_encode($response, JSON_PRETTY_PRINT); 