<?php
// Teste simples para IP brasileiro
echo "<h1>Teste Simples IP Brasileiro</h1>";

// Simular requisição interna
$_POST = [
    'eventType' => 'Init',
    'contentId' => 'shopify_store',
    'userId' => 'test_brazilian_user'
];

// Simular headers brasileiros
$_SERVER['HTTP_X_FORWARDED_FOR'] = '2804:1054:3018:4f70:692b:8a2a:2c02:3af5, 172.68.244.200';
$_SERVER['HTTP_CF_CONNECTING_IP'] = '2804:1054:3018:4f70:692b:8a2a:2c02:3af5';
$_SERVER['HTTP_TRUE_CLIENT_IP'] = '2804:1054:3018:4f70:692b:8a2a:2c02:3af5';
$_SERVER['HTTP_CF_IPCOUNTRY'] = 'BR';

echo "<p><strong>Headers simulados:</strong></p>";
echo "<pre>";
echo "X-Forwarded-For: " . $_SERVER['HTTP_X_FORWARDED_FOR'] . "\n";
echo "CF-Connecting-IP: " . $_SERVER['HTTP_CF_CONNECTING_IP'] . "\n";
echo "True-Client-IP: " . $_SERVER['HTTP_TRUE_CLIENT_IP'] . "\n";
echo "CF-IPCountry: " . $_SERVER['HTTP_CF_IPCOUNTRY'] . "\n";
echo "</pre>";

echo "<p><strong>POST Data:</strong></p>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<p>Execute este arquivo no servidor para testar a detecção de IP brasileiro.</p>";
echo "<p>URL: <a href='https://traqueamentophp.onrender.com/test-simple-brazilian.php'>https://traqueamentophp.onrender.com/test-simple-brazilian.php</a></p>";
?> 