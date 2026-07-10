# Multi-stage Dockerfile for Laravel (pesupeluh)

# 1) Build frontend assets with Node
FROM node:20-alpine AS node_builder
WORKDIR /app
COPY package*.json ./
RUN npm ci --legacy-peer-deps
COPY resources resources
COPY vite.config.js .
COPY vendor/tightenco/ziggy vendor/tightenco/ziggy
RUN npm run build

# 2) Install PHP dependencies with Composer
# 3) Production image with PHP-FPM
FROM php:8.3-fpm
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    zip \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && pecl install redis || true \
    && docker-php-ext-enable redis || true \
    && rm -rf /var/lib/apt/lists/*

# Copy application code
COPY . /var/www/html

# Install Composer via official installer and run composer install
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
 && php -r "unlink('composer-setup.php');" \
 && rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
 && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --no-progress --no-interaction \
 && php artisan package:discover --ansi

# Copy built frontend from node stage (if exists)
COPY --from=node_builder /app/public /var/www/html/public

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true

EXPOSE 9000
CMD ["php-fpm"]
