<?php
/**
 * Teste de geração de logs para verificar se o sistema está funcionando
 */

// Incluir o autoloader do Laravel
require_once '../vendor/autoload.php';

// Inicializar o Laravel
$app = require_once '../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\PixelLogger;
use Illuminate\Http\Request;

echo "🧪 Iniciando teste de logs...\n\n";

// Teste 1: Log de evento iniciado
echo "1. Testando log de evento iniciado...\n";
PixelLogger::logEventStart('ViewContent', [
    'contentId' => 'test_content',
    'userId' => 'test-user-123',
    'event_source_url' => 'https://test.com/product',
    '_fbc' => 'fb.test.123',
    '_fbp' => 'fb.test.456',
    'fn' => 'João',
    'ln' => 'Silva',
    'em' => 'test@test.com',
    'ph' => '11999999999'
]);

// Teste 2: Log de GeoIP
echo "2. Testando log de GeoIP...\n";
PixelLogger::logGeoIP('192.168.1.1', [
    'country' => 'br',
    'state' => 'sp',
    'city' => 'sao paulo',
    'postal_code' => '01000',
    'latitude' => -23.5505,
    'longitude' => -46.6333,
    'country_hash' => hash('sha256', 'br'),
    'state_hash' => hash('sha256', 'sp'),
    'city_hash' => hash('sha256', 'sao paulo'),
    'postal_hash' => hash('sha256', '01000')
]);

// Teste 3: Log de envio para Facebook
echo "3. Testando log de envio para Facebook...\n";
PixelLogger::logFacebookSend('ViewContent', 'test-event-123', [
    'external_id' => 'test-user-123',
    'client_ip_address' => '192.168.1.1',
    'client_user_agent' => 'Mozilla/5.0 Test Browser',
    'fbc' => 'fb.test.123',
    'fbp' => 'fb.test.456',
    'country' => 'br',
    'state' => 'sp',
    'city' => 'sao paulo',
    'postal_code' => '01000',
    'fn' => hash('sha256', 'joão'),
    'ln' => hash('sha256', 'silva'),
    'em' => hash('sha256', 'test@test.com'),
    'ph' => hash('sha256', '11999999999')
], [
    'content_ids' => ['test_content'],
    'content_type' => 'product'
]);

// Teste 4: Log de sucesso
echo "4. Testando log de sucesso...\n";
PixelLogger::logEventSuccess('ViewContent', 'test-event-123', 'test-user-123');

// Teste 5: Log de configuração
echo "5. Testando log de configuração...\n";
PixelLogger::logPixelConfig('test_content', [
    'pixel_id' => '123456789',
    'access_token' => 'test_token_123',
    'test_code' => 'TEST123'
]);

// Teste 6: Log de detecção de IP
echo "6. Testando log de detecção de IP...\n";
$mockRequest = new Request();
$mockRequest->headers->set('X-Forwarded-For', '192.168.1.1, 10.0.0.1');
$mockRequest->headers->set('CF-Connecting-IP', '192.168.1.1');
PixelLogger::logIPDetection($mockRequest, '192.168.1.1');

echo "\n✅ Testes concluídos!\n";
echo "📁 Verifique o arquivo: storage/logs/PixelTracker.log\n";
echo "🌐 Acesse o dashboard: /pixel-dashboard.php\n";

// Verificar se o arquivo foi criado
$logFile = '../storage/logs/PixelTracker.log';
if (file_exists($logFile)) {
    $size = filesize($logFile);
    echo "📊 Arquivo de log criado com {$size} bytes\n";
    
    // Mostrar as últimas linhas
    $content = file_get_contents($logFile);
    $lines = explode("\n", trim($content));
    $lastLines = array_slice($lines, -3);
    
    echo "\n📋 Últimas linhas do log:\n";
    foreach ($lastLines as $line) {
        if (!empty($line)) {
            echo "  " . substr($line, 0, 100) . "...\n";
        }
    }
} else {
    echo "❌ Arquivo de log não foi criado!\n";
    echo "🔍 Verifique as permissões da pasta storage/logs/\n";
    echo "🔍 Verifique a configuração em config/logging.php\n";
}
?> 