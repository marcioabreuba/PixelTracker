<?php
// Script para testar os logs detalhados de debug
echo "<h1>Teste de Logs Detalhados - PixelTracker</h1>";

// Fazer uma requisição de teste para o endpoint
$url = 'https://traqueamentophp.onrender.com/events/send';
$data = [
    'eventType' => 'PageView',
    'contentId' => 'shopify_store',
    'event_source_url' => 'https://test.com',
    '_fbc' => 'fb.1.1234567890.1234567890',
    '_fbp' => 'fb.1.1234567890.1234567890',
    'userId' => 'test_user_' . time(),
    'fn' => 'João',
    'ln' => 'Silva',
    'em' => 'joao@test.com',
    'ph' => '11999999999'
];

$options = [
    'http' => [
        'header' => [
            "Content-Type: application/x-www-form-urlencoded",
            "User-Agent: Test-Debug-Agent",
            "X-Forwarded-For: 2804:1054:3018:4f70:692b:8a2a:2c02:3af5, 172.68.244.200",
            "CF-Connecting-IP: 2804:1054:3018:4f70:692b:8a2a:2c02:3af5",
            "X-Real-IP: 2804:1054:3018:4f70:692b:8a2a:2c02:3af5"
        ],
        'method' => 'POST',
        'content' => http_build_query($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "<h2>Resultado da Requisição:</h2>";
echo "<pre>" . htmlspecialchars($result) . "</pre>";

echo "<h2>Headers de Resposta:</h2>";
echo "<pre>";
print_r($http_response_header);
echo "</pre>";

echo "<p><strong>Aguarde alguns segundos e verifique os logs em:</strong></p>";
echo "<p><a href='https://traqueamentophp.onrender.com/events-logs.php' target='_blank'>Ver Logs de Eventos</a></p>";

echo "<p><em>Timestamp do teste: " . date('Y-m-d H:i:s') . "</em></p>";
?> 