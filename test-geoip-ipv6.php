<?php
// Teste local de GeoIP com IPv6
require_once 'vendor/autoload.php';

use GeoIp2\Database\Reader;

echo "<h1>Teste Local GeoIP IPv6</h1>";

$geoipPath = 'storage/app/geoip/GeoLite2-City.mmdb';
$brazilianIPv6 = '2804:1054:3018:4f70:692b:8a2a:2c02:3af5';

echo "<p><strong>Arquivo GeoIP:</strong> $geoipPath</p>";
echo "<p><strong>IP IPv6 Brasileiro:</strong> $brazilianIPv6</p>";

try {
    if (!file_exists($geoipPath)) {
        throw new Exception("Arquivo GeoIP não encontrado: $geoipPath");
    }
    
    $fileSize = filesize($geoipPath);
    echo "<p><strong>Tamanho do arquivo:</strong> " . number_format($fileSize / 1024 / 1024, 2) . " MB</p>";
    
    if ($fileSize < 100) {
        throw new Exception("Arquivo GeoIP muito pequeno, pode estar corrompido");
    }
    
    $reader = new Reader($geoipPath);
    echo "<p>✅ Arquivo GeoIP carregado com sucesso</p>";
    
    // Teste com IPv6 brasileiro
    echo "<h2>Testando IPv6 Brasileiro:</h2>";
    $record = $reader->city($brazilianIPv6);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Valor</th></tr>";
    echo "<tr><td>IP Testado</td><td>$brazilianIPv6</td></tr>";
    echo "<tr><td>País</td><td>" . $record->country->isoCode . " (" . $record->country->name . ")</td></tr>";
    echo "<tr><td>Estado</td><td>" . $record->mostSpecificSubdivision->isoCode . " (" . $record->mostSpecificSubdivision->name . ")</td></tr>";
    echo "<tr><td>Cidade</td><td>" . $record->city->name . "</td></tr>";
    echo "<tr><td>CEP</td><td>" . $record->postal->code . "</td></tr>";
    echo "<tr><td>Latitude</td><td>" . $record->location->latitude . "</td></tr>";
    echo "<tr><td>Longitude</td><td>" . $record->location->longitude . "</td></tr>";
    echo "<tr><td>Precisão</td><td>" . $record->location->accuracyRadius . " km</td></tr>";
    echo "</table>";
    
    // Teste com IPv4 brasileiro conhecido
    echo "<h2>Testando IPv4 Brasileiro (para comparação):</h2>";
    $brazilianIPv4 = '200.160.2.3'; // IP brasileiro conhecido
    try {
        $record4 = $reader->city($brazilianIPv4);
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td>IP Testado</td><td>$brazilianIPv4</td></tr>";
        echo "<tr><td>País</td><td>" . $record4->country->isoCode . " (" . $record4->country->name . ")</td></tr>";
        echo "<tr><td>Estado</td><td>" . $record4->mostSpecificSubdivision->isoCode . " (" . $record4->mostSpecificSubdivision->name . ")</td></tr>";
        echo "<tr><td>Cidade</td><td>" . $record4->city->name . "</td></tr>";
        echo "<tr><td>CEP</td><td>" . $record4->postal->code . "</td></tr>";
        echo "<tr><td>Latitude</td><td>" . $record4->location->latitude . "</td></tr>";
        echo "<tr><td>Longitude</td><td>" . $record4->location->longitude . "</td></tr>";
        echo "</table>";
    } catch (Exception $e) {
        echo "<p>❌ Erro ao testar IPv4: " . $e->getMessage() . "</p>";
    }
    
    // Teste com IP americano para comparação
    echo "<h2>Testando IP Americano (para comparação):</h2>";
    $americanIP = '216.24.60.190'; // IP que apareceu nos logs
    try {
        $recordUS = $reader->city($americanIP);
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td>IP Testado</td><td>$americanIP</td></tr>";
        echo "<tr><td>País</td><td>" . $recordUS->country->isoCode . " (" . $recordUS->country->name . ")</td></tr>";
        echo "<tr><td>Estado</td><td>" . $recordUS->mostSpecificSubdivision->isoCode . " (" . $recordUS->mostSpecificSubdivision->name . ")</td></tr>";
        echo "<tr><td>Cidade</td><td>" . $recordUS->city->name . "</td></tr>";
        echo "<tr><td>CEP</td><td>" . $recordUS->postal->code . "</td></tr>";
        echo "<tr><td>Latitude</td><td>" . $recordUS->location->latitude . "</td></tr>";
        echo "<tr><td>Longitude</td><td>" . $recordUS->location->longitude . "</td></tr>";
        echo "</table>";
    } catch (Exception $e) {
        echo "<p>❌ Erro ao testar IP americano: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p>Trace: " . $e->getTraceAsString() . "</p>";
}

echo "<p><em>Teste executado em: " . date('Y-m-d H:i:s') . "</em></p>";
?> 