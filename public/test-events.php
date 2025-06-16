<?php
// Teste específico para /events/send
header('Content-Type: application/json');

try {
    // Simular uma requisição POST para /events/send
    $postData = [
        'eventType' => 'Init',
        'event_source_url' => 'https://test.com',
        'contentId' => 'test123',
        'userId' => 'user123'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://traqueamentophp.onrender.com/events/send');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'User-Agent: Test-Agent'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo json_encode([
        'status' => 'TEST_COMPLETED',
        'http_code' => $httpCode,
        'response' => $response,
        'post_data' => $postData,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
    
} catch (\Exception $e) {
    echo json_encode([
        'status' => 'TEST_ERROR',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
} 