<?php
/**
 * Script para limpar logs do PixelTracker
 * Uso: php clear-logs.php
 */

$logFile = '../storage/logs/laravel.log';

if (file_exists($logFile)) {
    // Fazer backup antes de limpar (opcional)
    $backupFile = '../storage/logs/laravel_backup_' . date('Y-m-d_H-i-s') . '.log';
    
    if (filesize($logFile) > 0) {
        copy($logFile, $backupFile);
        echo "✅ Backup criado: " . basename($backupFile) . "\n";
    }
    
    // Limpar o arquivo de log
    file_put_contents($logFile, '');
    echo "🗑️ Logs limpos com sucesso!\n";
    echo "📁 Arquivo: " . $logFile . "\n";
    echo "⏰ Data/Hora: " . date('d/m/Y H:i:s') . "\n";
} else {
    echo "❌ Arquivo de log não encontrado: " . $logFile . "\n";
}
?> 