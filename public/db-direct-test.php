<?php
// Teste direto de conexão com banco
header('Content-Type: application/json');

try {
    // Testar conexão PDO direta
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '5432';
    $database = $_ENV['DB_DATABASE'] ?? 'laravel';
    $username = $_ENV['DB_USERNAME'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    
    $dsn = "pgsql:host=$host;port=$port;dbname=$database";
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    // Testar uma query simples
    $stmt = $pdo->query('SELECT version()');
    $version = $stmt->fetchColumn();
    
    echo json_encode([
        'status' => 'DB_CONNECTED',
        'host' => $host,
        'port' => $port,
        'database' => $database,
        'username' => $username,
        'postgres_version' => $version,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (\Exception $e) {
    echo json_encode([
        'status' => 'DB_ERROR',
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'env_vars' => [
            'DB_HOST' => $_ENV['DB_HOST'] ?? 'NOT_SET',
            'DB_PORT' => $_ENV['DB_PORT'] ?? 'NOT_SET',
            'DB_DATABASE' => $_ENV['DB_DATABASE'] ?? 'NOT_SET',
            'DB_USERNAME' => $_ENV['DB_USERNAME'] ?? 'NOT_SET',
            'DB_PASSWORD' => !empty($_ENV['DB_PASSWORD']) ? 'SET' : 'NOT_SET'
        ]
    ]);
} 