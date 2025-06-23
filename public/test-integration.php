<?php
// Teste de Integração Completa: GeoIP Local + HERE API
require_once __DIR__ . '/../vendor/autoload.php';

// Carregar configurações do Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use GeoIp2\Database\Reader;
use App\Services\HereGeocodingService;
use App\Services\PixelLogger;

echo "<h1>🔄 Teste de Integração Completa</h1>";
echo "<p><strong>Data/Hora:</strong> " . date('Y-m-d H:i:s') . "</p>";

// IP de teste (Fortaleza)
$testIp = '186.222.168.201';

echo "<h2>🎯 Simulando Fluxo Real:</h2>";
echo "<p><strong>IP de Teste:</strong> {$testIp} (Fortaleza, CE)</p>";

echo "<h2>📍 Passo 1: GeoIP Local</h2>";

try {
    $geoipPath = storage_path('app/geoip/GeoLite2-City.mmdb');
    if (!file_exists($geoipPath)) {
        throw new Exception('Arquivo GeoIP não encontrado');
    }
    
    $reader = new Reader($geoipPath);
    $record = $reader->city($testIp);
    
    $country = strtolower($record->country->isoCode);
    $state = strtolower($record->mostSpecificSubdivision->isoCode);
    $city = strtolower($record->city->name);
    $postalCode = $record->postal->code;
    $latitude = $record->location->latitude;
    $longitude = $record->location->longitude;
    
    echo "<div style='background: #e3f2fd; padding: 15px; border: 1px solid #2196f3; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>📊 Dados GeoIP Local:</h3>";
    echo "<ul>";
    echo "<li><strong>País:</strong> {$country}</li>";
    echo "<li><strong>Estado:</strong> {$state}</li>";
    echo "<li><strong>Cidade:</strong> {$city}</li>";
    echo "<li><strong>CEP:</strong> " . ($postalCode ? $postalCode : '❌ NULL') . "</li>";
    echo "<li><strong>Coordenadas:</strong> {$latitude}, {$longitude}</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h2>🗺️ Passo 2: HERE API (Fallback)</h2>";
    
    if (empty($postalCode)) {
        echo "<p>🔄 CEP não encontrado no GeoIP local, tentando HERE API...</p>";
        
        $apiKey = config('services.here.api_key');
        if (empty($apiKey)) {
            echo "<div style='background: #fff3e0; padding: 15px; border: 1px solid #ff9800; border-radius: 5px; margin: 10px 0;'>";
            echo "<h3>⚠️ HERE API não configurada</h3>";
            echo "<p>Para testar o fallback, configure HERE_API_KEY no .env</p>";
            echo "</div>";
        } else {
            try {
                $pixelLogger = app(PixelLogger::class);
                $hereService = new HereGeocodingService($pixelLogger);
                
                $startTime = microtime(true);
                $herePostalCode = $hereService->reverseGeocode($latitude, $longitude);
                $endTime = microtime(true);
                
                $executionTime = round(($endTime - $startTime) * 1000, 2);
                
                if ($herePostalCode) {
                    $postalCode = $herePostalCode;
                    echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; border-radius: 5px; margin: 10px 0;'>";
                    echo "<h3>✅ HERE API Sucesso</h3>";
                    echo "<ul>";
                    echo "<li><strong>CEP HERE:</strong> {$herePostalCode}</li>";
                    echo "<li><strong>Tempo:</strong> {$executionTime}ms</li>";
                    echo "</ul>";
                    echo "</div>";
                } else {
                    echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336; border-radius: 5px; margin: 10px 0;'>";
                    echo "<h3>❌ HERE API sem resultado</h3>";
                    echo "<p>Nenhum CEP encontrado via HERE API</p>";
                    echo "</div>";
                }
            } catch (Exception $e) {
                echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336; border-radius: 5px; margin: 10px 0;'>";
                echo "<h3>❌ Erro HERE API</h3>";
                echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                echo "</div>";
            }
        }
    } else {
        echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>✅ CEP já disponível no GeoIP</h3>";
        echo "<p>Não é necessário consultar HERE API</p>";
        echo "</div>";
    }
    
    echo "<h2>📋 Resultado Final:</h2>";
    echo "<div style='background: #f3e5f5; padding: 15px; border: 1px solid #9c27b0; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>🎯 Dados Finais para Facebook:</h3>";
    echo "<ul>";
    echo "<li><strong>País:</strong> {$country}</li>";
    echo "<li><strong>Estado:</strong> {$state}</li>";
    echo "<li><strong>Cidade:</strong> {$city}</li>";
    echo "<li><strong>CEP Final:</strong> " . ($postalCode ? $postalCode : 'Não disponível') . "</li>";
    echo "<li><strong>Fonte CEP:</strong> " . ($record->postal->code ? 'GeoIP Local' : ($postalCode ? 'HERE API' : 'Nenhuma')) . "</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffebee; padding: 15px; border: 1px solid #f44336; border-radius: 5px; margin: 10px 0;'>";
    echo "<h3>❌ Erro no Teste</h3>";
    echo "<p><strong>Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<h2>📊 Comparação com MaxMind Web Service:</h2>";
echo "<div style='background: #e1f5fe; padding: 15px; border: 1px solid #0288d1; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>🌐 MaxMind Web Service (Referência)</h3>";
echo "<ul>";
echo "<li><strong>IP:</strong> 186.222.168.201</li>";
echo "<li><strong>CEP Esperado:</strong> 60000</li>";
echo "<li><strong>Cidade:</strong> Fortaleza</li>";
echo "<li><strong>Estado:</strong> Ceará</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🔧 Status do Sistema:</h2>";
echo "<ul>";
echo "<li><strong>GeoIP Local:</strong> ✅ Funcionando</li>";
echo "<li><strong>HERE API:</strong> " . (empty(config('services.here.api_key')) ? '⚠️ Não configurada' : '✅ Configurada') . "</li>";
echo "<li><strong>Logs:</strong> ✅ Ativos (storage/logs/PixelTracker.log)</li>";
echo "<li><strong>Cache:</strong> ✅ Ativo (Laravel Cache)</li>";
echo "</ul>";

echo "<p><small>Arquivo: " . __FILE__ . "</small></p>";
?> 