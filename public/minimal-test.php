<?php
// Teste mínimo do Laravel
header('Content-Type: application/json');

try {
    // Definir variáveis de ambiente necessárias
    $_ENV['SESSION_DRIVER'] = 'file';
    
    // Carregar autoloader
    require_once '/var/www/html/vendor/autoload.php';
    
    // Carregar aplicação
    $app = require_once '/var/www/html/bootstrap/app.php';
    
    // Testar sem usar helpers
    echo json_encode([
        'status' => 'MINIMAL_LARAVEL_OK',
        'session_driver' => $_ENV['SESSION_DRIVER'] ?? 'not_set',
        'app_loaded' => true,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (\Exception $e) {
    echo json_encode([
        'status' => 'ERROR',
        'message' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile())
    ]);
} 