<?php
/**
 * Gerar logs de exemplo baseados nos eventos reais que estão acontecendo
 * Acesse via: https://traqueamentophp.onrender.com/generate-sample-logs.php
 */

// Incluir o autoloader do Laravel
require_once '../vendor/autoload.php';

// Inicializar o Laravel
$app = require_once '../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\PixelLogger;
use Illuminate\Http\Request;

header('Content-Type: text/plain; charset=utf-8');

echo "🎯 Gerando logs de exemplo baseados nos eventos reais...\n\n";

// Simular eventos baseados nos logs do servidor que você mostrou
$eventos = [
    [
        'tipo' => 'Init',
        'userId' => 'user-' . uniqid(),
        'ip' => '179.251.43.95', // IP real dos logs
        'location' => ['country' => 'br', 'state' => 'sp', 'city' => 'guarulhos', 'postal_code' => '07000']
    ],
    [
        'tipo' => 'PageView', 
        'userId' => 'user-' . uniqid(),
        'ip' => '177.74.236.9', // IP real dos logs
        'location' => ['country' => 'br', 'state' => 'mg', 'city' => 'diamantina', 'postal_code' => '39100']
    ],
    [
        'tipo' => 'ViewContent',
        'userId' => 'user-' . uniqid(), 
        'ip' => '186.222.168.201', // IP que estava sem CEP
        'location' => ['country' => 'br', 'state' => 'ce', 'city' => 'fortaleza', 'postal_code' => '60881']
    ],
    [
        'tipo' => 'Timer_1min',
        'userId' => 'user-' . uniqid(),
        'ip' => '179.251.43.95',
        'location' => ['country' => 'br', 'state' => 'sp', 'city' => 'guarulhos', 'postal_code' => '07000']
    ]
];

foreach ($eventos as $i => $evento) {
    $num = $i + 1;
    echo "{$num}. Gerando evento {$evento['tipo']}...\n";
    
    // 1. Log de início do evento
    PixelLogger::logEventStart($evento['tipo'], [
        'contentId' => 'shopify_store',
        'userId' => $evento['userId'],
        'event_source_url' => 'https://salveterrah.com.br/products/test-product',
        '_fbc' => 'fb.1.' . time() . '.test_fbc_' . $num,
        '_fbp' => 'fb.2.' . (time() - 86400) . '.test_fbp_' . $num,
        'fn' => null,
        'ln' => null,
        'em' => null,
        'ph' => null
    ]);
    
    // 2. Log de detecção de IP
    $mockRequest = new Request();
    $mockRequest->headers->set('X-Forwarded-For', $evento['ip'] . ', 162.158.79.23');
    $mockRequest->headers->set('CF-Connecting-IP', $evento['ip']);
    PixelLogger::logIPDetection($mockRequest, $evento['ip']);
    
    // 3. Log de GeoIP
    PixelLogger::logGeoIP($evento['ip'], [
        'country' => $evento['location']['country'],
        'state' => $evento['location']['state'], 
        'city' => $evento['location']['city'],
        'postal_code' => $evento['location']['postal_code'],
        'latitude' => rand(-30, 0) + (rand(0, 9999) / 10000), // Coordenadas aproximadas do Brasil
        'longitude' => rand(-50, -35) + (rand(0, 9999) / 10000),
        'country_hash' => hash('sha256', $evento['location']['country']),
        'state_hash' => hash('sha256', $evento['location']['state']),
        'city_hash' => hash('sha256', $evento['location']['city']),
        'postal_hash' => hash('sha256', $evento['location']['postal_code'])
    ]);
    
    // 4. Log de configuração do pixel
    PixelLogger::logPixelConfig('shopify_store', [
        'pixel_id' => '676999668497170',
        'access_token' => 'token_presente',
        'test_code' => 'test_code_presente'
    ]);
    
    // 5. Log de envio para Facebook
    $eventId = uniqid('event_');
    PixelLogger::logFacebookSend($evento['tipo'], $eventId, [
        'external_id' => $evento['userId'],
        'client_ip_address' => $evento['ip'],
        'client_user_agent' => 'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Mobile Safari/537.36',
        'fbc' => 'fb.1.' . time() . '.test_fbc_' . $num,
        'fbp' => 'fb.2.' . (time() - 86400) . '.test_fbp_' . $num,
        'country' => $evento['location']['country'],
        'state' => $evento['location']['state'],
        'city' => $evento['location']['city'], 
        'postal_code' => $evento['location']['postal_code'],
        'fn' => '',
        'ln' => '',
        'em' => '',
        'ph' => ''
    ], [
        'content_ids' => ['shopify_store']
    ]);
    
    // 6. Log de sucesso
    PixelLogger::logEventSuccess($evento['tipo'], $eventId, $evento['userId']);
    
    echo "   ✅ Evento {$evento['tipo']} processado com sucesso!\n\n";
    
    // Pequena pausa para simular processamento real
    usleep(100000); // 0.1 segundo
}

echo "🎉 Logs de exemplo gerados com sucesso!\n\n";

// Verificar estatísticas do arquivo
$logFile = '../storage/logs/PixelTracker.log';
if (file_exists($logFile)) {
    $size = filesize($logFile);
    $content = file_get_contents($logFile);
    $lines = count(explode("\n", trim($content)));
    
    echo "📊 Estatísticas do arquivo de log:\n";
    echo "   📁 Arquivo: PixelTracker.log\n";
    echo "   📏 Tamanho: " . number_format($size) . " bytes\n";
    echo "   📄 Linhas: {$lines}\n";
    echo "   🕒 Última modificação: " . date('d/m/Y H:i:s', filemtime($logFile)) . "\n\n";
    
    echo "🌐 Agora acesse o dashboard para ver os logs:\n";
    echo "   https://traqueamentophp.onrender.com/pixel-dashboard.php\n\n";
    
    echo "🔄 Se precisar limpar os logs:\n";
    echo "   https://traqueamentophp.onrender.com/clear-logs.php\n";
} else {
    echo "❌ Erro: Arquivo de log não foi criado!\n";
}
?> 