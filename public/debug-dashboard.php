<?php
/**
 * Debug do Dashboard - Investigar por que não está funcionando
 * Acesse: https://traqueamentophp.onrender.com/debug-dashboard.php
 */

header('Content-Type: text/plain; charset=utf-8');

echo "🔍 DEBUG DO DASHBOARD - Investigação Completa\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// 1. Verificar arquivos de log disponíveis
echo "1. ARQUIVOS DE LOG DISPONÍVEIS:\n";
$logDir = '../storage/logs/';
if (is_dir($logDir)) {
    $files = scandir($logDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $path = $logDir . $file;
            $size = file_exists($path) ? filesize($path) : 0;
            $modified = file_exists($path) ? date('Y-m-d H:i:s', filemtime($path)) : 'N/A';
            echo "   📁 {$file} - {$size} bytes - Modificado: {$modified}\n";
        }
    }
} else {
    echo "   ❌ Diretório de logs não encontrado!\n";
}

echo "\n";

// 2. Verificar conteúdo dos arquivos principais
$logFiles = ['PixelTracker.log', 'laravel.log'];
foreach ($logFiles as $logFile) {
    echo "2. CONTEÚDO DE {$logFile}:\n";
    $path = $logDir . $logFile;
    if (file_exists($path)) {
        $size = filesize($path);
        echo "   📏 Tamanho: {$size} bytes\n";
        
        if ($size > 0) {
            $content = file_get_contents($path);
            $lines = explode("\n", trim($content));
            echo "   📄 Total de linhas: " . count($lines) . "\n";
            echo "   📋 Últimas 3 linhas:\n";
            $lastLines = array_slice($lines, -3);
            foreach ($lastLines as $i => $line) {
                if (!empty($line)) {
                    echo "      " . ($i + 1) . ". " . substr($line, 0, 100) . "...\n";
                }
            }
        } else {
            echo "   📭 Arquivo vazio\n";
        }
    } else {
        echo "   ❌ Arquivo não existe\n";
    }
    echo "\n";
}

// 3. Testar o regex do dashboard
echo "3. TESTE DO REGEX DO DASHBOARD:\n";
$testLogLines = [
    '[2025-06-23 22:48:05] production.INFO: 🚀 EVENTO INICIADO {"tipo":"ViewContent","origem":"WEB"}',
    '[2025-06-23 22:48:05] production.INFO: 🌍 GEOIP PROCESSADO {"ip_cliente":"192.168.1.1"}',
    '[2025-06-23 22:48:05] production.INFO: 📤 ENVIADO PARA FACEBOOK {"evento":"ViewContent"}',
    '[2025-06-23 22:48:05] production.INFO: ✅ EVENTO CONCLUÍDO {"tipo":"ViewContent"}',
    '[2025-06-23 22:48:05] production.ERROR: ❌ ERRO {"contexto":"teste"}'
];

$regex = '/\[(.*?)\].*?(🚀|🌍|📤|✅|❌|⚙️|🔍|🗺️|🔄)([^{]*)\{(.*)\}$/';

foreach ($testLogLines as $i => $line) {
    echo "   Linha " . ($i + 1) . ": ";
    if (preg_match($regex, $line, $matches)) {
        echo "✅ MATCH - Timestamp: {$matches[1]}, Emoji: {$matches[2]}, Título: " . trim($matches[3]) . "\n";
    } else {
        echo "❌ NO MATCH\n";
    }
}

echo "\n";

// 4. Simular processamento do dashboard
echo "4. SIMULAÇÃO DO PROCESSAMENTO DO DASHBOARD:\n";
$logFile = '../storage/logs/PixelTracker.log';
$logs = [];
$stats = ['total' => 0, 'eventos' => 0, 'sucessos' => 0, 'erros' => 0, 'usuarios_unicos' => []];

if (file_exists($logFile) && filesize($logFile) > 0) {
    $content = file_get_contents($logFile);
    $lines = array_filter(explode("\n", $content));
    echo "   📄 Total de linhas no arquivo: " . count($lines) . "\n";
    
    $processedLines = 0;
    foreach ($lines as $lineNum => $line) {
        if (strpos($line, 'production.INFO:') !== false || strpos($line, 'production.ERROR:') !== false) {
            if (preg_match($regex, $line, $matches)) {
                $processedLines++;
                $timestamp = $matches[1];
                $icon = $matches[2];
                $title = trim($matches[3]);
                $jsonData = '{' . $matches[4] . '}';
                $data = json_decode($jsonData, true);
                
                if ($data !== null) {
                    $logs[] = [
                        'timestamp' => $timestamp,
                        'icon' => $icon,
                        'title' => $title,
                        'data' => $data
                    ];
                    
                    $stats['total']++;
                    if (strpos($title, 'EVENTO INICIADO') !== false) $stats['eventos']++;
                    if (strpos($title, 'EVENTO CONCLUÍDO') !== false) $stats['sucessos']++;
                    if (strpos($title, 'ERRO') !== false) $stats['erros']++;
                    
                    if (isset($data['dados_recebidos']['userId'])) {
                        $stats['usuarios_unicos'][$data['dados_recebidos']['userId']] = true;
                    }
                    if (isset($data['external_id'])) {
                        $stats['usuarios_unicos'][$data['external_id']] = true;
                    }
                } else {
                    echo "   ⚠️ Linha " . ($lineNum + 1) . ": JSON inválido - " . substr($jsonData, 0, 50) . "...\n";
                }
            }
        }
    }
    
    echo "   📊 Linhas processadas: {$processedLines}\n";
    echo "   📈 Logs válidos encontrados: " . count($logs) . "\n";
    echo "   📋 Estatísticas:\n";
    echo "      - Total: {$stats['total']}\n";
    echo "      - Eventos: {$stats['eventos']}\n";
    echo "      - Sucessos: {$stats['sucessos']}\n";
    echo "      - Erros: {$stats['erros']}\n";
    echo "      - Usuários únicos: " . count($stats['usuarios_unicos']) . "\n";
    
    if (count($logs) > 0) {
        echo "   📝 Exemplo do primeiro log encontrado:\n";
        $firstLog = $logs[0];
        echo "      Timestamp: {$firstLog['timestamp']}\n";
        echo "      Emoji: {$firstLog['icon']}\n";
        echo "      Título: {$firstLog['title']}\n";
        echo "      Dados: " . json_encode($firstLog['data'], JSON_UNESCAPED_UNICODE) . "\n";
    }
} else {
    echo "   ❌ Arquivo de log não existe ou está vazio\n";
}

echo "\n";

// 5. Verificar permissões
echo "5. VERIFICAÇÃO DE PERMISSÕES:\n";
$paths = [
    '../storage/logs/',
    '../storage/logs/PixelTracker.log',
    '../storage/logs/laravel.log'
];

foreach ($paths as $path) {
    echo "   📁 {$path}: ";
    if (file_exists($path)) {
        echo "Existe";
        if (is_readable($path)) echo " | Legível";
        if (is_writable($path)) echo " | Gravável";
        echo "\n";
    } else {
        echo "❌ Não existe\n";
    }
}

echo "\n";

// 6. Informações do ambiente
echo "6. INFORMAÇÕES DO AMBIENTE:\n";
echo "   🐘 PHP Version: " . PHP_VERSION . "\n";
echo "   📂 Working Directory: " . getcwd() . "\n";
echo "   🕒 Timezone: " . date_default_timezone_get() . "\n";
echo "   📅 Current Time: " . date('Y-m-d H:i:s') . "\n";

echo "\n";
echo "🎯 CONCLUSÃO:\n";
if (count($logs) > 0) {
    echo "✅ Sistema funcionando! Logs foram encontrados e processados.\n";
    echo "🔗 Acesse o dashboard: https://traqueamentophp.onrender.com/pixel-dashboard.php\n";
} else {
    echo "❌ Problema identificado: Nenhum log válido foi encontrado.\n";
    echo "🔧 Soluções possíveis:\n";
    echo "   1. Gerar logs de teste: https://traqueamentophp.onrender.com/generate-sample-logs.php\n";
    echo "   2. Verificar se eventos reais estão sendo processados\n";
    echo "   3. Verificar configuração de logging no Laravel\n";
}
?> 