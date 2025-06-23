<?php
// Dashboard para visualizar logs do PixelTracker
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PixelTracker Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f6fa;
            color: #2c3e50;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 1.8rem;
            font-weight: 600;
        }

        .header p {
            opacity: 0.9;
            margin-top: 0.5rem;
        }

        .controls {
            background: white;
            padding: 1rem;
            border-bottom: 1px solid #e1e8ed;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            padding: 1rem;
            background: white;
            border-bottom: 1px solid #e1e8ed;
        }

        .stat-card {
            text-align: center;
            padding: 1rem;
            border-radius: 8px;
            background: #f8f9fa;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            min-height: calc(100vh - 120px);
        }

        .log-entry {
            border-bottom: 1px solid #e1e8ed;
            padding: 1rem;
            transition: background 0.2s;
        }

        .log-entry:hover {
            background: #f8f9fa;
        }

        .log-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .log-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            margin-right: 0.75rem;
        }

        .log-title {
            font-weight: 600;
            font-size: 1rem;
            flex: 1;
        }

        .log-time {
            color: #7f8c8d;
            font-size: 0.85rem;
        }

        .log-details {
            margin-left: 2.5rem;
            font-size: 0.9rem;
        }

        .log-data {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 0.75rem;
            margin-top: 0.5rem;
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
            overflow-x: auto;
        }

        .evento-iniciado { background: #e3f2fd; color: #1976d2; }
        .geoip { background: #e8f5e8; color: #388e3c; }
        .facebook { background: #fff3e0; color: #f57c00; }
        .sucesso { background: #e8f5e8; color: #388e3c; }
        .erro { background: #ffebee; color: #d32f2f; }
        .config { background: #f3e5f5; color: #7b1fa2; }
        .ip { background: #e1f5fe; color: #0277bd; }

        .highlight {
            background: #fff59d !important;
            animation: pulse 2s;
        }

        @keyframes pulse {
            0% { background: #fff59d; }
            50% { background: #ffeb3b; }
            100% { background: #fff59d; }
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }

        .empty-state img {
            width: 64px;
            height: 64px;
            opacity: 0.5;
            margin-bottom: 1rem;
        }

        .filter-tabs {
            display: flex;
            background: white;
            border-bottom: 1px solid #e1e8ed;
            overflow-x: auto;
        }

        .filter-tab {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            white-space: nowrap;
            transition: all 0.2s;
        }

        .filter-tab:hover {
            background: #f8f9fa;
        }

        .filter-tab.active {
            border-bottom-color: #3498db;
            color: #3498db;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .log-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎯 PixelTracker Dashboard</h1>
        <p>Monitoramento em tempo real dos eventos do Facebook Pixel</p>
    </div>

    <div class="container">
        <div class="controls">
            <div>
                <button class="btn btn-primary" onclick="location.reload()">
                    🔄 Atualizar
                </button>
                <button class="btn btn-success" onclick="autoRefresh()">
                    ⚡ Auto-refresh
                </button>
            </div>
            <div>
                <a href="?clear=logs" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja limpar todos os logs?')">
                    🗑️ Limpar Logs
                </a>
            </div>
        </div>

        <?php
        // Limpar logs se solicitado
        if (isset($_GET['clear']) && $_GET['clear'] === 'logs') {
            $logFile = '../storage/logs/laravel.log';
            if (file_exists($logFile)) {
                file_put_contents($logFile, '');
                echo '<div style="background: #d4edda; color: #155724; padding: 1rem; margin: 1rem; border-radius: 6px; border: 1px solid #c3e6cb;">
                        ✅ Logs limpos com sucesso!
                      </div>';
            }
        }

        // Ler logs
        $logFile = '../storage/logs/laravel.log';
        $logs = [];
        $stats = [
            'total' => 0,
            'eventos' => 0,
            'sucessos' => 0,
            'erros' => 0,
            'usuarios_unicos' => []
        ];

        if (file_exists($logFile) && filesize($logFile) > 0) {
            $content = file_get_contents($logFile);
            $lines = array_filter(explode("\n", $content));
            $lines = array_reverse($lines); // Mais recentes primeiro
            
            foreach ($lines as $line) {
                if (strpos($line, 'production.INFO:') !== false || strpos($line, 'production.ERROR:') !== false) {
                    preg_match('/\[(.*?)\].*?(🚀|🌍|📤|✅|❌|⚙️|🔍|🗺️|🔄)([^{]*)\{(.*)\}$/', $line, $matches);
                    
                    if (count($matches) >= 5) {
                        $timestamp = $matches[1];
                        $icon = $matches[2];
                        $title = trim($matches[3]);
                        $jsonData = '{' . $matches[4] . '}';
                        $data = json_decode($jsonData, true);
                        
                        $logs[] = [
                            'timestamp' => $timestamp,
                            'icon' => $icon,
                            'title' => $title,
                            'data' => $data,
                            'raw' => $line
                        ];
                        
                        $stats['total']++;
                        
                        // Contadores específicos
                        if (strpos($title, 'EVENTO INICIADO') !== false) $stats['eventos']++;
                        if (strpos($title, 'EVENTO CONCLUÍDO') !== false) $stats['sucessos']++;
                        if (strpos($title, 'ERRO') !== false) $stats['erros']++;
                        
                        // Usuários únicos
                        if (isset($data['dados_recebidos']['userId'])) {
                            $stats['usuarios_unicos'][$data['dados_recebidos']['userId']] = true;
                        }
                        if (isset($data['external_id'])) {
                            $stats['usuarios_unicos'][$data['external_id']] = true;
                        }
                    }
                }
            }
        }
        ?>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total'] ?></div>
                <div class="stat-label">Total de Logs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['eventos'] ?></div>
                <div class="stat-label">Eventos Iniciados</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['sucessos'] ?></div>
                <div class="stat-label">Sucessos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($stats['usuarios_unicos']) ?></div>
                <div class="stat-label">Usuários Únicos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['erros'] ?></div>
                <div class="stat-label">Erros</div>
            </div>
        </div>

        <div class="filter-tabs">
            <div class="filter-tab active" onclick="filterLogs('all')">Todos</div>
            <div class="filter-tab" onclick="filterLogs('evento')">🚀 Eventos</div>
            <div class="filter-tab" onclick="filterLogs('geoip')">🌍 GeoIP</div>
            <div class="filter-tab" onclick="filterLogs('facebook')">📤 Facebook</div>
            <div class="filter-tab" onclick="filterLogs('sucesso')">✅ Sucessos</div>
            <div class="filter-tab" onclick="filterLogs('erro')">❌ Erros</div>
        </div>

        <div id="logs-container">
            <?php if (empty($logs)): ?>
                <div class="empty-state">
                    <div style="font-size: 3rem;">📊</div>
                    <h3>Nenhum log encontrado</h3>
                    <p>Os logs aparecerão aqui quando eventos forem processados</p>
                </div>
            <?php else: ?>
                <?php foreach (array_slice($logs, 0, 50) as $index => $log): ?>
                    <div class="log-entry" data-type="<?= getLogType($log['title']) ?>">
                        <div class="log-header">
                            <div style="display: flex; align-items: center; flex: 1;">
                                <div class="log-icon <?= getLogClass($log['title']) ?>">
                                    <?= $log['icon'] ?>
                                </div>
                                <div class="log-title"><?= htmlspecialchars($log['title']) ?></div>
                            </div>
                            <div class="log-time"><?= formatTime($log['timestamp']) ?></div>
                        </div>
                        <div class="log-details">
                            <?= formatLogDetails($log['data'], $log['title']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        let autoRefreshInterval = null;

        function autoRefresh() {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
                autoRefreshInterval = null;
                document.querySelector('.btn-success').textContent = '⚡ Auto-refresh';
            } else {
                autoRefreshInterval = setInterval(() => {
                    location.reload();
                }, 5000);
                document.querySelector('.btn-success').textContent = '⏹️ Parar';
            }
        }

        function filterLogs(type) {
            // Atualizar tabs
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            event.target.classList.add('active');

            // Filtrar logs
            document.querySelectorAll('.log-entry').forEach(entry => {
                if (type === 'all' || entry.dataset.type === type) {
                    entry.style.display = 'block';
                } else {
                    entry.style.display = 'none';
                }
            });
        }

        // Destacar novos logs
        function highlightNew() {
            const entries = document.querySelectorAll('.log-entry');
            if (entries.length > 0) {
                entries[0].classList.add('highlight');
                setTimeout(() => {
                    entries[0].classList.remove('highlight');
                }, 3000);
            }
        }

        // Auto-scroll para logs importantes
        document.addEventListener('DOMContentLoaded', () => {
            const errorLogs = document.querySelectorAll('[data-type="erro"]');
            if (errorLogs.length > 0) {
                errorLogs[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    </script>
</body>
</html>

<?php
function getLogType($title) {
    if (strpos($title, 'EVENTO INICIADO') !== false) return 'evento';
    if (strpos($title, 'GEOIP') !== false) return 'geoip';
    if (strpos($title, 'FACEBOOK') !== false) return 'facebook';
    if (strpos($title, 'EVENTO CONCLUÍDO') !== false) return 'sucesso';
    if (strpos($title, 'ERRO') !== false) return 'erro';
    if (strpos($title, 'CONFIGURAÇÃO') !== false) return 'config';
    if (strpos($title, 'DETECÇÃO') !== false) return 'ip';
    return 'other';
}

function getLogClass($title) {
    if (strpos($title, 'EVENTO INICIADO') !== false) return 'evento-iniciado';
    if (strpos($title, 'GEOIP') !== false) return 'geoip';
    if (strpos($title, 'FACEBOOK') !== false) return 'facebook';
    if (strpos($title, 'EVENTO CONCLUÍDO') !== false) return 'sucesso';
    if (strpos($title, 'ERRO') !== false) return 'erro';
    if (strpos($title, 'CONFIGURAÇÃO') !== false) return 'config';
    if (strpos($title, 'DETECÇÃO') !== false) return 'ip';
    return 'other';
}

function formatTime($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'agora';
    if ($diff < 3600) return floor($diff/60) . 'm atrás';
    if ($diff < 86400) return floor($diff/3600) . 'h atrás';
    return date('d/m H:i', $time);
}

function formatLogDetails($data, $title) {
    $html = '';
    
    if (strpos($title, 'EVENTO INICIADO') !== false) {
        $html .= '<strong>Tipo:</strong> ' . ($data['tipo'] ?? 'N/A') . '<br>';
        $html .= '<strong>User ID:</strong> ' . substr($data['dados_recebidos']['userId'] ?? 'N/A', 0, 8) . '...<br>';
        $html .= '<strong>Content ID:</strong> ' . ($data['dados_recebidos']['contentId'] ?? 'N/A');
    }
    
    elseif (strpos($title, 'GEOIP') !== false) {
        $geo = $data['dados_geograficos'] ?? [];
        $html .= '<strong>IP:</strong> ' . ($data['ip_cliente'] ?? 'N/A') . '<br>';
        $html .= '<strong>Localização:</strong> ' . ($geo['cidade'] ?? 'N/A') . ', ' . strtoupper($geo['estado'] ?? 'N/A') . ', ' . strtoupper($geo['pais'] ?? 'N/A') . '<br>';
        $html .= '<strong>CEP:</strong> ' . ($geo['cep'] ?? 'null');
    }
    
    elseif (strpos($title, 'FACEBOOK') !== false) {
        $userData = $data['dados_usuario_enviados'] ?? [];
        $geo = $userData['dados_geograficos'] ?? [];
        $html .= '<strong>Evento:</strong> ' . ($data['evento'] ?? 'N/A') . '<br>';
        $html .= '<strong>Event ID:</strong> ' . substr($data['event_id'] ?? 'N/A', 0, 8) . '...<br>';
        $html .= '<strong>Localização:</strong> ' . ($geo['city'] ?? 'N/A') . ', ' . strtoupper($geo['state'] ?? 'N/A') . '<br>';
        $html .= '<strong>CEP:</strong> ' . ($geo['postal_code'] ?? 'null');
    }
    
    elseif (strpos($title, 'EVENTO CONCLUÍDO') !== false) {
        $html .= '<strong>Tipo:</strong> ' . ($data['tipo'] ?? 'N/A') . '<br>';
        $html .= '<strong>Status:</strong> <span style="color: #27ae60; font-weight: bold;">' . ($data['status'] ?? 'N/A') . '</span><br>';
        $html .= '<strong>Event ID:</strong> ' . substr($data['event_id'] ?? 'N/A', 0, 8) . '...';
    }
    
    elseif (strpos($title, 'ERRO') !== false) {
        $html .= '<strong>Contexto:</strong> ' . ($data['contexto'] ?? 'N/A') . '<br>';
        $html .= '<strong>Erro:</strong> <span style="color: #e74c3c;">' . ($data['erro'] ?? 'N/A') . '</span>';
    }
    
    else {
        // Dados genéricos
        foreach ($data as $key => $value) {
            if (is_array($value)) continue;
            $html .= '<strong>' . ucfirst($key) . ':</strong> ' . htmlspecialchars(substr($value, 0, 50)) . '<br>';
        }
    }
    
    return $html;
}
?> 