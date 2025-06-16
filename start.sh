#!/bin/bash

echo "=== INICIANDO APLICAÇÃO ==="
echo "PORT: $PORT"
echo "APP_ENV: $APP_ENV"
echo "APP_KEY exists: $([ -n "$APP_KEY" ] && echo "YES" || echo "NO")"

# Aguardar um pouco para garantir que tudo esteja pronto
sleep 2

echo "=== CRIANDO DIRETÓRIOS NECESSÁRIOS ==="
# Criar diretório de sessões se não existir
mkdir -p /var/www/html/storage/framework/sessions
chmod -R 755 /var/www/html/storage/framework/sessions
chown -R www-data:www-data /var/www/html/storage/framework/sessions

echo "=== LIMPANDO CACHE ==="
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