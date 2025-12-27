FROM php:8.3-fpm

# Install nginx and required PHP extensions
RUN apt-get update && apt-get install -y \
    nginx \
    git unzip curl libpng-dev libonig-dev libxml2-dev zip libgmp-dev netcat-openbsd \
    && docker-php-ext-install pdo_mysql mbstring exif bcmath gd gmp \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Copy application files
COPY . .

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php \
    -- --install-dir=/usr/local/bin --filename=composer

# Install PHP dependencies
WORKDIR /app/core
RUN composer install --no-dev --optimize-autoloader

# Set proper permissions
RUN chown -R www-data:www-data /app/core/storage /app/core/bootstrap/cache \
    && chmod -R 775 /app/core/storage /app/core/bootstrap/cache

# Configure PHP for better error reporting (can be overridden by .env)
RUN echo "display_errors = On" >> /usr/local/etc/php/conf.d/docker-php-errors.ini \
    && echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/docker-php-errors.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/docker-php-errors.ini \
    && echo "error_log = /var/log/php_errors.log" >> /usr/local/etc/php/conf.d/docker-php-errors.ini

# Configure nginx for Laravel
RUN rm -f /etc/nginx/sites-enabled/default \
    && echo "server {" > /etc/nginx/conf.d/default.conf \
    && echo "    listen 3000;" >> /etc/nginx/conf.d/default.conf \
    && echo "    server_name _;" >> /etc/nginx/conf.d/default.conf \
    && echo "    root /app/core/public;" >> /etc/nginx/conf.d/default.conf \
    && echo "    index index.php;" >> /etc/nginx/conf.d/default.conf \
    && echo "    client_max_body_size 100M;" >> /etc/nginx/conf.d/default.conf \
    && echo "    error_log /var/log/nginx/error.log;" >> /etc/nginx/conf.d/default.conf \
    && echo "    access_log /var/log/nginx/access.log;" >> /etc/nginx/conf.d/default.conf \
    && echo "    location / {" >> /etc/nginx/conf.d/default.conf \
    && echo "        try_files \$uri \$uri/ /index.php?\$query_string;" >> /etc/nginx/conf.d/default.conf \
    && echo "    }" >> /etc/nginx/conf.d/default.conf \
    && echo "    location ~ \.php$ {" >> /etc/nginx/conf.d/default.conf \
    && echo "        include fastcgi_params;" >> /etc/nginx/conf.d/default.conf \
    && echo "        fastcgi_pass 127.0.0.1:9000;" >> /etc/nginx/conf.d/default.conf \
    && echo "        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;" >> /etc/nginx/conf.d/default.conf \
    && echo "        fastcgi_param PATH_INFO \$fastcgi_path_info;" >> /etc/nginx/conf.d/default.conf \
    && echo "        fastcgi_read_timeout 300;" >> /etc/nginx/conf.d/default.conf \
    && echo "        fastcgi_index index.php;" >> /etc/nginx/conf.d/default.conf \
    && echo "    }" >> /etc/nginx/conf.d/default.conf \
    && echo "    location ~ /\.(?!well-known).* {" >> /etc/nginx/conf.d/default.conf \
    && echo "        deny all;" >> /etc/nginx/conf.d/default.conf \
    && echo "    }" >> /etc/nginx/conf.d/default.conf \
    && echo "}" >> /etc/nginx/conf.d/default.conf

# Configure PHP-FPM to listen on TCP (for nginx in same container)
RUN sed -i 's/listen = \/run\/php\/php8.3-fpm.sock/listen = 127.0.0.1:9000/' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/;clear_env = no/clear_env = no/' /usr/local/etc/php-fpm.d/www.conf

# Create startup script to run both nginx and php-fpm
RUN echo '#!/bin/bash' > /start.sh \
    && echo 'set -e' >> /start.sh \
    && echo '' >> /start.sh \
    && echo '# Start PHP-FPM in background' >> /start.sh \
    && echo 'php-fpm -D' >> /start.sh \
    && echo '' >> /start.sh \
    && echo '# Wait for PHP-FPM to be ready' >> /start.sh \
    && echo 'until nc -z 127.0.0.1 9000; do' >> /start.sh \
    && echo '  echo "Waiting for PHP-FPM..."' >> /start.sh \
    && echo '  sleep 1' >> /start.sh \
    && echo 'done' >> /start.sh \
    && echo '' >> /start.sh \
    && echo 'echo "PHP-FPM is ready, starting nginx..."' >> /start.sh \
    && echo '' >> /start.sh \
    && echo '# Start nginx in foreground' >> /start.sh \
    && echo 'nginx -g "daemon off;"' >> /start.sh \
    && chmod +x /start.sh

# Expose port 3000 (matches Coolify's default)
EXPOSE 3000

CMD ["/start.sh"]
