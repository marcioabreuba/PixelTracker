<?php
// Teste direto do Laravel sem middlewares
try {
    // Carregar o autoloader
    require_once '/var/www/html/vendor/autoload.php';
    
    // Carregar a aplicação Laravel
    $app = require_once '/var/www/html/bootstrap/app.php';
    
    // Testar se o Laravel carrega
    echo json_encode([
        'status' => 'LARAVEL_LOADED',
        'app_name' => config('app.name'),
        'app_env' => config('app.env'),
        'app_debug' => config('app.debug'),
        'timestamp' => now()->toDateTimeString()
    ]);
    
} catch (\Exception $e) {
    echo json_encode([
        'status' => 'ERROR',
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
} 