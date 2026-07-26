# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage 1 (vendor): scarica SOLO i pacchetti PHP necessari alla build front-end.
# Il tema Filament (resources/css/filament/admin/theme.css) fa
# `@import '/vendor/filament/filament/resources/css/theme.css'` e ha `@source`
# su `vendor/laravel/framework/.../Pagination` — file che Vite/Tailwind leggono a
# build time. Senza `vendor/` presente, `npm run build` fallirebbe sull'@import
# mancante. Si usa l'immagine ufficiale `composer:2` perché il base FrankenPHP
# NON include il binario `composer`. `--no-scripts --no-autoloader`: qui servono
# solo i file dei pacchetti, non l'autoloader dell'app (rigenerato nello stage
# finale); `--ignore-platform-reqs` perché l'immagine composer non ha tutte le
# estensioni PHP di runtime (intl/pcntl/...), irrilevanti per il solo download.
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist --ignore-platform-reqs

# ---------------------------------------------------------------------------
# Stage 2 (assets): compila gli asset Vite (CSS/JS + tema Filament) in
# public/build/. `public/build/` è gitignored, quindi NON è nel contesto di build
# git-checkout: va rigenerato qui, altrimenti ogni pagina del pannello che usa
# `->viteTheme(...)` lancia ViteManifestNotFoundException (gotcha documentato in
# CLAUDE.md, US-005/US-024). `fakerphp`/dev-tools non c'entrano: è puro build Node.
# ---------------------------------------------------------------------------
FROM node:20-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
# Sorgenti lette da Vite/Tailwind: config, entrypoint CSS/JS + i `@source` del tema
# (resources/views/filament e app/Filament) e il `vendor/` dello stage precedente.
COPY vite.config.js ./
COPY resources ./resources
COPY app ./app
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# ---------------------------------------------------------------------------
# Stage finale: immagine di runtime FrankenPHP.
# ---------------------------------------------------------------------------
FROM dunglas/frankenphp:php8.4-alpine

# Il base FrankenPHP non include `composer`: va copiato dall'immagine ufficiale
# (stesso pattern di docker/php/Dockerfile, lo stack di sviluppo).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

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

# `.dockerignore` esclude storage/framework/{cache,sessions,views}: vanno
# ricreate, altrimenti `php artisan ...` (a build time via post-autoload-dump e a
# runtime nell'entrypoint) fallisce con "Please provide a valid cache path".
RUN mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# `--no-scripts`: il post-autoload-dump lancerebbe `php artisan package:discover`,
# che a build time fallisce/congelerebbe stato prima che l'ambiente reale sia
# iniettato — stessa ragione per cui NON si esegue qui `php artisan config:cache`/
# `event:cache`: a build time env_file non ha ancora le variabili reali (DB_HOST,
# DB_PASSWORD, APP_KEY, ...). La discovery dei pacchetti e la cache di config/eventi
# avvengono a runtime in docker/uat/app-entrypoint.sh, quando l'ambiente c'è.
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Asset Vite compilati nello stage `assets`: devono atterrare accanto al resto
# dell'app, altrimenti `public/build/manifest.json` manca e il pannello va in 500.
COPY --from=assets /app/public/build /app/public/build

ENV SERVER_NAME=:8000
EXPOSE 8000

COPY docker/uat/app-entrypoint.sh /app-entrypoint.sh
RUN chmod +x /app-entrypoint.sh

ENTRYPOINT ["/app-entrypoint.sh"]
