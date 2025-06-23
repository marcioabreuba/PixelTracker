<?php
// Teste HERE Geocoding API
// Coordenadas de Fortaleza: -3.7145, -38.5419

require_once __DIR__ . '/../vendor/autoload.php';

// Carregar configurações do Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\HereGeocodingService;
use App\Services\PixelLogger;

echo "<h1>🗺️ Teste HERE Geocoding API</h1>";
echo "<p><strong>Data/Hora:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Coordenadas de teste (Fortaleza)
$latitude = -3.7145;
$longitude = -38.5419;

echo "<h2>📍 Coordenadas de Teste:</h2>";
echo "<ul>";
echo "<li><strong>Latitude:</strong> {$latitude}</li>";
echo "<li><strong>Longitude:</strong> {$longitude}</li>";
echo "<li><strong>Localização:</strong> Fortaleza, CE</li>";
echo "</ul>";

// Verificar configuração
$apiKey = config('services.here.api_key');
echo "<h2>⚙️ Configuração:</h2>";
echo "<ul>";
echo "<li><strong>HERE API Key:</strong> " . (empty($apiKey) ? '❌ NÃO CONFIGURADA' : '✅ CONFIGURADA (' . substr($apiKey, 0, 10) . '...)') . "</li>";
echo "<li><strong>Base URL:</strong> " . config('services.here.base_url') . "</li>";
echo "<li><strong>Daily Limit:</strong> " . number_format(config('services.here.daily_limit')) . "</li>";
echo "</ul>";

if (empty($apiKey)) {
    echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>❌ Erro de Configuração</h3>";
    echo "<p>HERE_API_KEY não está configurada no arquivo .env</p>";
    echo "<p>Para obter uma chave gratuita:</p>";
    echo "<ol>";
    echo "<li>Acesse: <a href='https://developer.here.com/' target='_blank'>https://developer.here.com/</a></li>";
    echo "<li>Crie uma conta gratuita</li>";
    echo "<li>Crie um projeto</li>";
    echo "<li>Gere uma API Key</li>";
    echo "<li>Adicione no .env: HERE_API_KEY=sua_chave_aqui</li>";
    echo "</ol>";
    echo "</div>";
} else {
    echo "<h2>🧪 Executando Teste...</h2>";
    
    try {
        $pixelLogger = app(PixelLogger::class);
        $hereService = new HereGeocodingService($pixelLogger);
        
        $startTime = microtime(true);
        $postalCode = $hereService->reverseGeocode($latitude, $longitude);
        $endTime = microtime(true);
        
        $executionTime = round(($endTime - $startTime) * 1000, 2);
        
        echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>✅ Resultado do Teste</h3>";
        echo "<ul>";
        echo "<li><strong>CEP Encontrado:</strong> " . ($postalCode ? $postalCode : 'Não encontrado') . "</li>";
        echo "<li><strong>Tempo de Execução:</strong> {$executionTime}ms</li>";
        echo "<li><strong>Status:</strong> " . ($postalCode ? 'SUCESSO' : 'SEM RESULTADO') . "</li>";
        echo "</ul>";
        echo "</div>";
        
        // Mostrar estatísticas de uso
        $stats = $hereService->getUsageStats();
        echo "<h2>📊 Estatísticas de Uso:</h2>";
        echo "<ul>";
        echo "<li><strong>Uso Hoje:</strong> {$stats['daily_usage']}/{$stats['daily_limit']} ({$stats['daily_percentage']}%)</li>";
        echo "<li><strong>Requests Restantes:</strong> " . number_format($stats['daily_remaining']) . "</li>";
        echo "<li><strong>Data:</strong> {$stats['date']}</li>";
        echo "</ul>";
        
    } catch (Exception $e) {
        echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>❌ Erro na Execução</h3>";
        echo "<p><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>Arquivo:</strong> " . $e->getFile() . ":" . $e->getLine() . "</p>";
        echo "</div>";
    }
}

echo "<h2>📋 Próximos Passos:</h2>";
echo "<ol>";
echo "<li>Configurar HERE_API_KEY no .env se ainda não foi feito</li>";
echo "<li>Testar com diferentes coordenadas</li>";
echo "<li>Verificar logs em storage/logs/PixelTracker.log</li>";
echo "<li>Monitorar uso diário da API</li>";
echo "</ol>";

echo "<p><small>Arquivo: " . __FILE__ . "</small></p>";
?> 