#!/bin/bash

echo "=== INICIANDO APLICAÇÃO ==="
echo "PORT: $PORT"
echo "APP_ENV: $APP_ENV"
echo "APP_KEY exists: $([ -n "$APP_KEY" ] && echo "YES" || echo "NO")"

# Forçar sessões em arquivo
export SESSION_DRIVER=file

# Aguardar um pouco para garantir que tudo esteja pronto
sleep 2

echo "=== CRIANDO DIRETÓRIOS NECESSÁRIOS ==="
# Criar diretório de sessões se não existir
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/app/geoip
chmod -R 755 /var/www/html/storage/framework
chown -R www-data:www-data /var/www/html/storage/framework

# Verificar se o arquivo GeoIP existe, se não, tentar baixar
echo "=== VERIFICANDO GEOIP ==="
if [ ! -f "/var/www/html/storage/app/geoip/GeoLite2-City.mmdb" ]; then
    echo "Arquivo GeoIP não encontrado, tentando baixar..."
    if [ ! -z "$MAXMIND_LICENSE_KEY" ] && [ ! -z "$MAXMIND_ACCOUNT_ID" ]; then
        php artisan geoip:update || echo "⚠️ Falha ao baixar GeoIP - continuando sem geolocalização"
    else
        echo "⚠️ Credenciais MaxMind não configuradas - geolocalização desabilitada"
    fi
else
    echo "✅ Arquivo GeoIP encontrado"
fi

echo "=== LIMPEZA AGRESSIVA DE CACHE ==="
# Remover arquivos de cache manualmente
rm -rf /var/www/html/storage/framework/cache/*
rm -rf /var/www/html/storage/framework/views/*
rm -rf /var/www/html/bootstrap/cache/*

# Limpar todos os caches possíveis
php artisan config:clear || echo "Config clear failed"
php artisan cache:clear || echo "Cache clear failed"
php artisan route:clear || echo "Route clear failed"
php artisan view:clear || echo "View clear failed"

echo "=== CONFIGURANDO APACHE ==="
# Configurar Apache para usar a porta do Render
sed -i "s/Listen 80/Listen $PORT/" /etc/apache2/ports.conf
sed -i "s/:80/:$PORT/" /etc/apache2/sites-available/000-default.conf

echo "=== INICIANDO APACHE ==="
# Iniciar Apache em foreground
apache2-foreground 