# syntax=docker/dockerfile:1
FROM dunglas/frankenphp:php8.4-alpine

# Estensioni PHP alineate a docker/php/Dockerfile (Fase 0, stack di sviluppo):
# pdo/pdo_pgsql/zip/intl/bcmath/opcache/pcntl/exif + redis (pecl li).
# pcntl e' richiesta da "php artisan queue:work"/"schedule:work" (gestione
# graceful shutdown su SIGTERM, importante per il worker container, vedi
# docker/uat/worker-entrypoint.sh) e non era nell'elenco di esempio del brief.
RUN install-php-extensions \
    pdo \
    pdo_pgsql \
    redis \
    zip \
    exif \
    bcmath \
    intl \
    opcache \
    pcntl

WORKDIR /app
COPY . /app

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && php artisan config:cache \
    && php artisan event:cache

ENV SERVER_NAME=:8000
EXPOSE 8000

CMD ["frankenphp", "php-server", "--listen", ":8000", "--root", "/app/public"]
