FROM php:5.6-apache

# --- php:5.6-apache is Debian Stretch, now archived. Point apt at the archive,
#     drop the dead stretch-updates suite, and trust it (GPG keys are expired). ---
RUN set -eux; \
    printf 'deb [trusted=yes] http://archive.debian.org/debian stretch main\n' \
        > /etc/apt/sources.list; \
    echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/10no-check-valid-until

# --- System libs + PHP extensions the app needs (gd for barcodes/PDF, zip+mbstring for PhpSpreadsheet/mPDF) ---
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libzip-dev zlib1g-dev libxml2-dev libicu-dev; \
    docker-php-ext-configure gd --with-freetype-dir=/usr --with-jpeg-dir=/usr; \
    docker-php-ext-install -j"$(nproc)" \
        pdo_mysql mysqli gd zip mbstring bcmath intl opcache; \
    a2enmod rewrite; \
    rm -rf /var/lib/apt/lists/*

# Runtime overrides (see php.ini in this dir). Note: the app's .htaccess also sets
# php_value memory_limit/max_input_vars via mod_php and will override these per-request.
COPY php.ini /usr/local/etc/php/conf.d/zz-pos.ini

WORKDIR /var/www/html
