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

# NON eseguire qui `php artisan config:cache`/`event:cache`: a build time
# env_file non ha ancora iniettato le variabili reali (DB_HOST, DB_PASSWORD,
# APP_KEY, ...), quindi la cache congelerebbe i default vuoti/sbagliati letti
# da .env.example (o nessun valore). Questi comandi girano invece a runtime in
# docker/uat/app-entrypoint.sh, dopo che l'ambiente del container e' disponibile.
RUN composer install --no-dev --optimize-autoloader --no-interaction

ENV SERVER_NAME=:8000
EXPOSE 8000

COPY docker/uat/app-entrypoint.sh /app-entrypoint.sh
RUN chmod +x /app-entrypoint.sh

ENTRYPOINT ["/app-entrypoint.sh"]
