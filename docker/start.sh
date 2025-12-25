#!/bin/bash

# Create .env file if it doesn't exist (Laravel will use environment variables)
if [ ! -f /var/www/html/core/.env ]; then
    echo "Creating minimal .env file..."
    echo "# Environment variables are injected by Coolify" > /var/www/html/core/.env
    echo "APP_NAME=${APP_NAME:-Laravel}" >> /var/www/html/core/.env
    echo "APP_ENV=${APP_ENV:-production}" >> /var/www/html/core/.env
    echo "APP_KEY=${APP_KEY:-}" >> /var/www/html/core/.env
    echo "APP_DEBUG=${APP_DEBUG:-false}" >> /var/www/html/core/.env
    echo "APP_URL=${APP_URL:-http://localhost}" >> /var/www/html/core/.env
    chown www-data:www-data /var/www/html/core/.env
    chmod 644 /var/www/html/core/.env
fi

# Set permissions
chown -R www-data:www-data /var/www/html/core/storage /var/www/html/core/bootstrap/cache
chmod -R 775 /var/www/html/core/storage /var/www/html/core/bootstrap/cache

# Start supervisor
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf

