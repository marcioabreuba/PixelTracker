<?php
// Teste do sistema de logging estruturado
require_once __DIR__ . '/../vendor/autoload.php';

// Configurar o ambiente Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Http\Kernel::class)->bootstrap();

use App\Services\PixelLogger;
use Illuminate\Http\Request;

header('Content-Type: application/json');

try {
    // Criar um request fake para teste
    $request = Request::create('/test', 'POST', [
        'eventType' => 'PageView',
        'contentId' => 'test-product',
        'userId' => 'test-user-123',
        '_fbc' => 'test-fbc',
        '_fbp' => 'test-fbp',
        'fn' => 'João',
        'ln' => 'Silva',
        'em' => 'joao@test.com',
        'ph' => '11999999999'
    ]);

    // Testar os diferentes tipos de logs
    PixelLogger::logEventStart('PageView', $request->all(), 'TEST');
    
    PixelLogger::logIPDetection($request, '192.168.1.1');
    
    PixelLogger::logGeoIP('192.168.1.1', [
        'country' => 'br',
        'state' => 'sp',
        'city' => 'sao paulo',
        'postal_code' => '01234-567',
        'latitude' => -23.5505,
        'longitude' => -46.6333,
        'country_hash' => hash('sha256', 'br'),
        'state_hash' => hash('sha256', 'sp'),
        'city_hash' => hash('sha256', 'sao paulo'),
        'postal_hash' => hash('sha256', '01234-567')
    ]);
    
    PixelLogger::logPixelConfig('test-product', [
        'pixel_id' => '123456789',
        'access_token' => 'test-token',
        'test_code' => 'TEST123'
    ]);
    
    PixelLogger::logFacebookSend('PageView', 'test-event-id-123', [
        'external_id' => 'test-user-123',
        'client_ip_address' => '192.168.1.1',
        'client_user_agent' => 'Test Browser',
        'fbc' => 'test-fbc',
        'fbp' => 'test-fbp',
        'country' => 'br',
        'state' => 'sp',
        'city' => 'sao paulo',
        'postal_code' => '01234-567',
        'fn' => 'João',
        'ln' => 'Silva',
        'em' => 'joao@test.com',
        'ph' => '11999999999'
    ], [
        'content_ids' => ['test-product']
    ]);
    
    PixelLogger::logEventSuccess('PageView', 'test-event-id-123', 'test-user-123');
    
    // Testar log de erro
    PixelLogger::logError('Teste', 'Este é um erro de teste', ['additional_data' => 'teste']);

    echo json_encode([
        'status' => 'success',
        'message' => 'Logs de teste foram gerados com sucesso!',
        'timestamp' => date('Y-m-d H:i:s'),
        'logs_generated' => [
            'event_start',
            'ip_detection',
            'geoip',
            'pixel_config',
            'facebook_send',
            'event_success',
            'error_test'
        ]
    ], JSON_PRETTY_PRINT);

} catch (\Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
} 