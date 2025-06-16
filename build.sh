#!/bin/bash

echo "🚀 Iniciando build para Render..."

# Instalar dependências do Composer
echo "📦 Instalando dependências PHP..."
composer install --no-dev --optimize-autoloader --no-interaction

# Gerar chave da aplicação se não existir
echo "🔑 Gerando chave da aplicação..."
php artisan key:generate --force

# Executar migrações
echo "🗄️ Executando migrações do banco de dados..."
php artisan migrate --force

# Criar tabelas necessárias se não existirem
echo "📋 Verificando estrutura do banco..."
php artisan migrate:status

# Otimizar aplicação para produção
echo "⚡ Otimizando para produção..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Baixar/atualizar banco de dados GeoIP (se configurado)
echo "🌍 Atualizando base de dados GeoIP..."
if [ ! -z "$MAXMIND_LICENSE_KEY" ]; then
    php artisan geoip:update || echo "⚠️ GeoIP update falhou - continuando sem erro"
else
    echo "ℹ️ MaxMind não configurado - pulando update do GeoIP"
fi

# Criar diretórios necessários
echo "📁 Criando diretórios necessários..."
mkdir -p storage/logs
mkdir -p storage/framework/cache
mkdir -p storage/framework/sessions
mkdir -p storage/framework/views
mkdir -p bootstrap/cache

# Ajustar permissões
echo "🔒 Ajustando permissões..."
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Verificar se a aplicação está funcional
echo "✅ Verificando configuração..."
php artisan config:show app.env
php artisan config:show app.key

echo "🎉 Build completado com sucesso!"
echo "📍 URL da aplicação: $APP_URL"
echo "🔗 Endpoints importantes:"
echo "   - Webhook Shopify: $APP_URL/webhook/shopify"
echo "   - Script Tracking: $APP_URL/shopify-tracking.js"
echo "   - Eventos: $APP_URL/events/send" 