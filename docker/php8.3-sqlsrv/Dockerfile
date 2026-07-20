FROM php:8.3-fpm

RUN set -eux; \
    export DEBIAN_FRONTEND=noninteractive; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates \
        apt-transport-https \
        gnupg \
        dirmngr \
        curl \
        git \
        unzip \
        libzip-dev \
        libpng-dev \
        libjpeg-dev \
        libwebp-dev \
        libfreetype6-dev \
        libonig-dev \
        libxml2-dev \
        libicu-dev \
        sqlite3 \
        libsqlite3-dev \
        unixodbc-dev \
        libssl-dev \
        build-essential \
        pkg-config \
        make \
        gcc \
        gnupg2 \
    ; \
    curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor --batch -o /usr/share/keyrings/microsoft-prod.gpg; \
    curl -fsSL https://packages.microsoft.com/config/debian/12/prod.list > /etc/apt/sources.list.d/mssql-release.list; \
    apt-get update; \
    ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql18; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-install pdo_mysql pdo_sqlite mbstring exif pcntl bcmath gd zip; \
    pecl channel-update pecl.php.net || true; \
    pecl install redis sqlsrv pdo_sqlsrv; \
    docker-php-ext-enable redis sqlsrv pdo_sqlsrv; \
    apt-get purge -y --auto-remove build-essential make gcc pkg-config dirmngr || true; \
    rm -rf /var/lib/apt/lists/* /tmp/pear; \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install Node.js and npm for Vite assets
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get install -y nodejs && \
    npm install -g npm@10

WORKDIR /var/www/pesupeluh
COPY . /var/www/pesupeluh
RUN mkdir -p /var/www/pesupeluh/bootstrap/cache /var/www/pesupeluh/storage/framework/cache /var/www/pesupeluh/storage/framework/sessions /var/www/pesupeluh/storage/framework/views /var/www/pesupeluh/storage/logs /var/www/pesupeluh/database && \
    chown -R www-data:www-data /var/www/pesupeluh/storage /var/www/pesupeluh/bootstrap/cache /var/www/pesupeluh/database || true && \
    composer install --no-interaction --prefer-dist --no-progress --no-scripts && \
    npm install --legacy-peer-deps && \
    npm run build && \
    php artisan package:discover --ansi || true

