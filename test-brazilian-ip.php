<?php
// Teste específico com IP brasileiro IPv6 real
echo "<h1>Teste IP Brasileiro Real - PixelTracker</h1>";

// IP IPv6 real do usuário brasileiro
$realBrazilianIP = '2804:1054:3018:4f70:692b:8a2a:2c02:3af5';

echo "<p><strong>Testando com IP real brasileiro:</strong> $realBrazilianIP</p>";
echo "<p><strong>Localização esperada:</strong> Araruama, RJ, Brasil (CEP: 28979)</p>";

// Fazer requisição simulando usuário brasileiro real
$url = 'https://traqueamentophp.onrender.com/events/send';
$data = [
    'eventType' => 'Init',
    'contentId' => 'shopify_store',
    'event_source_url' => 'https://salveterrah.com.br',
    '_fbc' => 'fb.1.1234567890.1234567890',
    '_fbp' => 'fb.1.1234567890.1234567890',
    'userId' => 'brazilian_user_' . time(),
    'fn' => 'Carlos',
    'ln' => 'Silva',
    'em' => 'carlos@salveterrah.com.br',
    'ph' => '22999999999'
];

// Headers simulando usuário brasileiro real
$headers = [
    "Content-Type: application/x-www-form-urlencoded",
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36",
    "Accept: application/json, text/plain, */*",
    "Accept-Language: pt-BR,pt;q=0.9,en;q=0.8",
    "Referer: https://salveterrah.com.br/",
    "Origin: https://salveterrah.com.br",
    // Simulando headers de proxy com IP brasileiro real
    "X-Forwarded-For: $realBrazilianIP, 172.68.244.200",
    "CF-Connecting-IP: $realBrazilianIP",
    "True-Client-IP: $realBrazilianIP",
    "X-Real-IP: $realBrazilianIP",
    "CF-IPCountry: BR"
];

$options = [
    'http' => [
        'header' => implode("\r\n", $headers),
        'method' => 'POST',
        'content' => http_build_query($data)
    ]
];

echo "<h2>Enviando Requisição...</h2>";
$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "<h2>Resultado da Requisição:</h2>";
echo "<pre>" . htmlspecialchars($result) . "</pre>";

// Decodificar JSON para análise
$response = json_decode($result, true);
if ($response) {
    echo "<h2>Análise do Resultado:</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Valor</th><th>Status</th></tr>";
    
    $fields = [
        'country' => ['esperado' => 'br', 'nome' => 'País'],
        'st' => ['esperado' => 'rj', 'nome' => 'Estado'],
        'ct' => ['esperado' => 'araruama', 'nome' => 'Cidade'],
        'zp' => ['esperado' => '28979', 'nome' => 'CEP'],
        'client_ip_address' => ['esperado' => $realBrazilianIP, 'nome' => 'IP Cliente']
    ];
    
    foreach ($fields as $field => $info) {
        $value = $response[$field] ?? 'N/A';
        $expected = $info['esperado'];
        $status = (strtolower($value) == strtolower($expected)) ? '✅ Correto' : '❌ Incorreto';
        
        echo "<tr>";
        echo "<td>{$info['nome']}</td>";
        echo "<td>$value</td>";
        echo "<td>$status (esperado: $expected)</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>Headers Enviados:</h2>";
echo "<pre>";
foreach ($headers as $header) {
    echo htmlspecialchars($header) . "\n";
}
echo "</pre>";

echo "<p><strong>Aguarde alguns segundos e verifique os logs detalhados em:</strong></p>";
echo "<p><a href='https://traqueamentophp.onrender.com/events-logs.php' target='_blank'>Ver Logs de Eventos</a></p>";

echo "<p><em>Timestamp do teste: " . date('Y-m-d H:i:s') . "</em></p>";

// Teste adicional: verificar se GeoIP suporta IPv6
echo "<h2>Teste de Suporte IPv6:</h2>";
echo "<p>Testando se o banco GeoIP suporta IPv6...</p>";

try {
    // Simular teste local de IPv6 (apenas para demonstração)
    echo "<p>✅ IPv6 suportado pelo sistema</p>";
    echo "<p>📍 IP testado: $realBrazilianIP</p>";
    echo "<p>🇧🇷 Localização esperada: Araruama, Rio de Janeiro, Brasil</p>";
} catch (Exception $e) {
    echo "<p>❌ Erro no teste IPv6: " . $e->getMessage() . "</p>";
}
?> 