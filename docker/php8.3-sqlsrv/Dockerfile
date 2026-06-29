FROM php:8.3-fpm

# Install build dependencies, Microsoft ODBC driver, and PHP sqlsrv/pdo_sqlsrv extensions
RUN set -eux; \
    export DEBIAN_FRONTEND=noninteractive; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        ca-certificates \
        apt-transport-https \
        gnupg \
        dirmngr \
        curl \
        build-essential \
        pkg-config \
        unixodbc-dev \
        libxml2-dev \
        libssl-dev \
        make \
        gcc \
    ; \
    curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor --batch -o /usr/share/keyrings/microsoft-prod.gpg; \
    curl -fsSL https://packages.microsoft.com/config/debian/12/prod.list > /etc/apt/sources.list.d/mssql-release.list; \
    apt-get update; \
    ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql18; \
    pecl channel-update pecl.php.net || true; \
    pecl install sqlsrv pdo_sqlsrv; \
    docker-php-ext-enable sqlsrv pdo_sqlsrv; \
    apt-get purge -y --auto-remove build-essential make gcc pkg-config dirmngr || true; \
    rm -rf /var/lib/apt/lists/* /tmp/pear

WORKDIR /var/www/pesupeluh

# Keep default php-fpm entrypoint/cmd
