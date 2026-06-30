This directory contains a Dockerfile to build a PHP 8.3 FPM image with Microsoft SQL Server PDO drivers (sqlsrv and pdo_sqlsrv).

Build and run (from project root):

```bash
# Build image
sudo docker build -t pesupeluh/php:8.3-sqlsrv -f docker/php8.3-sqlsrv/Dockerfile .

# Stop and remove any existing container, then run new container
sudo docker rm -f php83-fpm || true
sudo docker run -d --name php83-fpm \
  -v /var/www/pesupeluh:/var/www/pesupeluh \
  -v /var/www/genesys:/var/www/genesys \
  -p 127.0.0.1:9000:9000 \
  pesupeluh/php:8.3-sqlsrv

# Check logs
sudo docker logs php83-fpm --tail 200
```

Notes:
- The image installs the Microsoft ODBC driver for Debian 12 and the PECL sqlsrv/pdo_sqlsrv extensions.
- Adjust the Dockerfile if your host requires a different Microsoft package source.

CLI convenience:

- Use the project wrapper scripts in `bin/` to run PHP and Artisan inside the container:

```bash
# Run artisan using the containerized PHP (uses pdo_sqlsrv)
./bin/artisan-docker migrate --force

# Run PHP CLI inside container
./bin/php-docker -v
```

These avoid installing `pdo_sqlsrv` on the host and keep environments reproducible.
