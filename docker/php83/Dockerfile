# Phase 2 — PHP 8.3 + Yii 1.1.31. Same application code, modern runtime.
# Runs side-by-side with the PHP 5.6 baseline (:8082) for A/B comparison.
FROM php:8.3-apache

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libzip-dev zlib1g-dev libxml2-dev libicu-dev \
        unzip git; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" \
        pdo_mysql mysqli gd zip bcmath intl opcache ftp; \
    a2enmod rewrite; \
    rm -rf /var/lib/apt/lists/*

# Composer, for replacing the vendored PHPMailer 5.1 / mPDF / PHPExcel.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# php.ini logs to /var/log/php_errors.log, but /var/log is root-owned and
# Apache runs as www-data, so PHP would silently fall back to the SAPI logger.
# Pre-create the file owned by www-data so deprecations land somewhere greppable.
RUN touch /var/log/php_errors.log && chown www-data:www-data /var/log/php_errors.log

COPY php.ini /usr/local/etc/php/conf.d/zz-pos.ini

# The app's .htaccess sets `php_value memory_limit 10240M` (10 GB) and
# max_input_vars via mod_php, which would override any php.ini value.
# AllowOverride the rewrite rules but neutralise the php_value directives by
# re-setting sane limits after .htaccess is processed.
COPY zz-override.conf /etc/apache2/conf-enabled/zz-override.conf

WORKDIR /var/www/html
