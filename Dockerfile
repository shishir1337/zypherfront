FROM php:8.3-fpm

# Install nginx and required PHP extensions
RUN apt-get update && apt-get install -y \
    nginx \
    git unzip curl libpng-dev libonig-dev libxml2-dev zip \
    && docker-php-ext-install pdo_mysql mbstring exif bcmath gd \
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
RUN chmod -R 775 storage bootstrap/cache

# Configure nginx for Laravel
RUN rm -f /etc/nginx/sites-enabled/default \
    && echo "server {" > /etc/nginx/conf.d/default.conf \
    && echo "    listen 3000;" >> /etc/nginx/conf.d/default.conf \
    && echo "    server_name _;" >> /etc/nginx/conf.d/default.conf \
    && echo "    root /app/core/public;" >> /etc/nginx/conf.d/default.conf \
    && echo "    index index.php;" >> /etc/nginx/conf.d/default.conf \
    && echo "    client_max_body_size 100M;" >> /etc/nginx/conf.d/default.conf \
    && echo "    location / {" >> /etc/nginx/conf.d/default.conf \
    && echo "        try_files \$uri \$uri/ /index.php?\$query_string;" >> /etc/nginx/conf.d/default.conf \
    && echo "    }" >> /etc/nginx/conf.d/default.conf \
    && echo "    location ~ \.php$ {" >> /etc/nginx/conf.d/default.conf \
    && echo "        include fastcgi_params;" >> /etc/nginx/conf.d/default.conf \
    && echo "        fastcgi_pass 127.0.0.1:9000;" >> /etc/nginx/conf.d/default.conf \
    && echo "        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;" >> /etc/nginx/conf.d/default.conf \
    && echo "        fastcgi_param PATH_INFO \$fastcgi_path_info;" >> /etc/nginx/conf.d/default.conf \
    && echo "        fastcgi_read_timeout 300;" >> /etc/nginx/conf.d/default.conf \
    && echo "    }" >> /etc/nginx/conf.d/default.conf \
    && echo "}" >> /etc/nginx/conf.d/default.conf

# Configure PHP-FPM to listen on TCP (for nginx in same container)
RUN sed -i 's/listen = \/run\/php\/php8.3-fpm.sock/listen = 127.0.0.1:9000/' /usr/local/etc/php-fpm.d/www.conf

# Create startup script to run both nginx and php-fpm
RUN echo '#!/bin/bash' > /start.sh \
    && echo 'set -e' >> /start.sh \
    && echo 'php-fpm -D' >> /start.sh \
    && echo 'nginx -g "daemon off;"' >> /start.sh \
    && chmod +x /start.sh

# Expose port 3000 (matches Coolify's default)
EXPOSE 3000

CMD ["/start.sh"]
