# 🎯 PixelTracker Dashboard - Guia de Uso

## 📊 Dashboard Visual

O PixelTracker agora possui um dashboard visual moderno para monitorar os logs em tempo real, similar ao Meta Pixel Helper.

### 🌐 Acessar o Dashboard

```
https://seu-dominio.com/pixel-dashboard.php
```

### ✨ Funcionalidades

#### 📈 Estatísticas em Tempo Real
- **Total de Logs**: Contador geral de eventos processados
- **Eventos Iniciados**: Quantidade de eventos que começaram a ser processados
- **Sucessos**: Eventos enviados com sucesso para o Facebook
- **Usuários Únicos**: Contagem de usuários únicos identificados
- **Erros**: Quantidade de erros encontrados

#### 🔍 Filtros Inteligentes
- **Todos**: Visualizar todos os logs
- **🚀 Eventos**: Apenas eventos iniciados
- **🌍 GeoIP**: Logs de geolocalização
- **📤 Facebook**: Envios para Facebook
- **✅ Sucessos**: Apenas eventos bem-sucedidos
- **❌ Erros**: Apenas logs de erro

#### ⚡ Controles
- **🔄 Atualizar**: Recarregar manualmente
- **⚡ Auto-refresh**: Atualização automática a cada 5 segundos
- **🗑️ Limpar Logs**: Remover todos os logs (com confirmação)

### 📱 Design Responsivo

O dashboard é otimizado para:
- 💻 Desktop
- 📱 Mobile
- 📊 Tablets

---

## 🗑️ Limpeza de Logs

### 1. Via Dashboard Web

1. Acesse o dashboard: `https://seu-dominio.com/pixel-dashboard.php`
2. Clique em **🗑️ Limpar Logs**
3. Confirme a ação
4. ✅ Logs limpos com sucesso!

### 2. Via Terminal/SSH

```bash
# Navegar até a pasta public
cd /caminho/para/seu/projeto/public

# Executar script de limpeza
php clear-logs.php
```

**Saída esperada:**
```
✅ Backup criado: laravel_backup_2025-06-23_22-30-15.log
🗑️ Logs limpos com sucesso!
📁 Arquivo: ../storage/logs/laravel.log
⏰ Data/Hora: 23/06/2025 22:30:15
```

### 3. Via Comando Direto

```bash
# Limpar logs manualmente (sem backup)
echo "" > storage/logs/laravel.log

# Ou deletar o arquivo completamente
rm storage/logs/laravel.log
```

### 4. Automatização com Cron

Para limpar logs automaticamente:

```bash
# Editar crontab
crontab -e

# Adicionar linha para limpar logs toda segunda-feira às 02:00
0 2 * * 1 cd /caminho/para/projeto/public && php clear-logs.php
```

---

## 🔧 Configurações Avançadas

### Alterar Frequência do Auto-refresh

No arquivo `pixel-dashboard.php`, linha ~200:

```javascript
// Mudar de 5000ms (5s) para outro valor
autoRefreshInterval = setInterval(() => location.reload(), 5000);
```

### Alterar Quantidade de Logs Exibidos

No arquivo `pixel-dashboard.php`, linha ~180:

```php
// Mudar de 50 para outro número
<?php foreach (array_slice($logs, 0, 50) as $log): ?>
```

### Personalizar Cores

Edite as classes CSS no `<style>` do arquivo:

```css
.evento-iniciado { background: #e3f2fd; color: #1976d2; }
.geoip { background: #e8f5e8; color: #388e3c; }
.facebook { background: #fff3e0; color: #f57c00; }
```

---

## 🚨 Troubleshooting

### Dashboard não carrega

1. **Verificar permissões:**
   ```bash
   chmod 644 public/pixel-dashboard.php
   ```

2. **Verificar se logs existem:**
   ```bash
   ls -la storage/logs/laravel.log
   ```

3. **Verificar se PHP está funcionando:**
   ```bash
   php -v
   ```

### Logs não aparecem

1. **Verificar se eventos estão sendo gerados:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Verificar formato dos logs:**
   - Logs devem conter emojis (🚀, 🌍, 📤, etc.)
   - Formato JSON válido

### Auto-refresh não funciona

1. **Verificar JavaScript no navegador:**
   - Abrir DevTools (F12)
   - Verificar erros no Console

2. **Verificar se está em HTTPS:**
   - Algumas funcionalidades podem não funcionar em HTTP

---

## 📋 Exemplos de Uso

### Monitoramento em Produção

1. Deixar dashboard aberto com auto-refresh ativado
2. Filtrar por "❌ Erros" para monitorar problemas
3. Verificar estatísticas de usuários únicos

### Debug de Problemas

1. Filtrar por tipo específico de evento
2. Verificar detalhes de geolocalização
3. Confirmar envios para Facebook

### Limpeza Periódica

1. Fazer backup antes de limpar
2. Limpar quando logs ficarem muito grandes
3. Configurar limpeza automática via cron

---

## 🎨 Preview do Dashboard

```
🎯 PixelTracker Dashboard
Monitoramento em tempo real dos eventos do Facebook Pixel

[🔄 Atualizar] [⚡ Auto-refresh]                    [🗑️ Limpar Logs]

┌─────────────┬─────────────┬─────────────┬─────────────┬─────────────┐
│     223     │     45      │     38      │     12      │      2      │
│Total de Logs│   Eventos   │  Sucessos   │  Usuários   │    Erros    │
└─────────────┴─────────────┴─────────────┴─────────────┴─────────────┘

[Todos] [🚀 Eventos] [🌍 GeoIP] [📤 Facebook] [✅ Sucessos] [❌ Erros]

🚀 EVENTO INICIADO                                    2m atrás
   Tipo: ViewContent
   User ID: e0c3caa7...
   Content ID: shopify_store

🌍 GEOIP PROCESSADO                                   2m atrás  
   IP: 177.74.236.9
   Localização: Diamantina, MG, BR
   CEP: 39100

📤 ENVIADO PARA FACEBOOK                              2m atrás
   Evento: ViewContent
   Event ID: 7c2012f7...
   Localização: diamantina, mg
   CEP: 39100
```

---

## 🔗 Links Úteis

- [Documentação Facebook Conversions API](https://developers.facebook.com/docs/marketing-api/conversions-api)
- [Meta Pixel Helper](https://chrome.google.com/webstore/detail/meta-pixel-helper)
- [Laravel Logging](https://laravel.com/docs/logging)

---

**✨ Dashboard criado com ❤️ para facilitar o monitoramento do PixelTracker!** 