FROM node:24-bookworm-slim AS frontend
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build

FROM composer:2.9 AS composer_binary

FROM php:8.4-apache-bookworm AS application
WORKDIR /var/www/html
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev libjpeg62-turbo-dev libpng-dev libwebp-dev libonig-dev \
        libsqlite3-dev libxml2-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j2 gd mbstring pdo_sqlite dom xml zip opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer_binary /usr/bin/composer /usr/local/bin/composer
COPY . .
RUN mkdir -p bootstrap/cache storage/app/private storage/app/public \
        storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    && php artisan adminlte:install --only=assets --force \
    && chmod -R u=rwX,go=rX . \
    && chown -R www-data:www-data storage bootstrap/cache
COPY --from=frontend /build/public/build ./public/build
COPY --chmod=644 docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY --chmod=644 docker/php.ini /usr/local/etc/php/conf.d/medicina.ini
COPY --chmod=644 docker/mpm-prefork.conf /etc/apache2/mods-available/mpm_prefork.conf
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/medicina-entrypoint
RUN printf 'Listen ${PORT}\n' > /etc/apache2/ports.conf \
    && printf 'ServerTokens Prod\nServerSignature Off\n' > /etc/apache2/conf-available/medicina.conf \
    && a2enconf medicina \
    && chmod +x /usr/local/bin/medicina-entrypoint

ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr LOG_LEVEL=warning \
    DB_CONNECTION=sqlite DB_DATABASE=/var/www/html/storage/data/database.sqlite \
    SESSION_DRIVER=database SESSION_ENCRYPT=true SESSION_SECURE_COOKIE=true \
    CACHE_STORE=database QUEUE_CONNECTION=sync PORT=10000
EXPOSE 10000
ENTRYPOINT ["medicina-entrypoint"]
CMD ["apache2-foreground"]
