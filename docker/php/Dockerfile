# syntax=docker/dockerfile:1
FROM php:8.4-fpm-alpine

# UID/GID dell'utente host: risolvono in build-time il mismatch di permessi
# sui volumi bind-mount, senza chown manuale a runtime (§4.2 del PRD).
ARG UID=1000
ARG GID=1000

RUN apk add --no-cache \
        postgresql-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        fcgi \
        shadow \
    && apk add --no-cache --virtual .build-deps ${PHPIZE_DEPS} \
    && docker-php-ext-install pdo pdo_pgsql zip intl bcmath opcache pcntl exif \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && groupmod -o -g "${GID}" www-data \
    && usermod -o -u "${UID}" -g www-data www-data \
    && apk del .build-deps shadow \
    && rm -rf /tmp/pear /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www/html

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
