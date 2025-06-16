#!/bin/bash

# Set default port if not provided
export PORT=${PORT:-80}

# Wait for database to be ready
echo "Waiting for database..."
sleep 10

# Configure Apache to listen on the correct port
echo "Configuring Apache for port $PORT..."
echo "Listen $PORT" > /etc/apache2/ports.conf
echo "ServerName localhost:$PORT" >> /etc/apache2/apache2.conf

# Update VirtualHost configuration with actual port
sed -i "s/\${PORT}/$PORT/g" /etc/apache2/sites-available/000-default.conf

# Run Laravel commands
echo "Running Laravel setup..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Start Apache
echo "Starting Apache on port $PORT..."
apache2-foreground 