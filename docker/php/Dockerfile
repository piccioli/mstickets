# syntax=docker/dockerfile:1
FROM php:8.4-fpm-alpine

# UID/GID dell'utente host: risolvono in build-time il mismatch di permessi
# sui volumi bind-mount, senza chown manuale a runtime (§4.2 del PRD).
ARG UID=1000
ARG GID=1000

# `chromium`/`font-freefont`: motore di rendering del PDF di documentazione
# (spatie/laravel-pdf, driver `chrome-php/chrome`, §6.4.3 del PRD, US-406) —
# a differenza di pdflatex/texlive qui sopra (solo collaudo, escluso dall'immagine
# UAT/produzione), Chromium headless è un requisito di RUNTIME anche in produzione:
# questi PDF si generano in coda per utenti reali, non solo in dev. `apk` risolve
# da solo le librerie condivise necessarie (nss, freetype, harfbuzz, ...), nessun
# pacchetto Node/npm richiesto: il driver `chrome-php/chrome` è puro PHP (Chrome
# DevTools Protocol), non passa da Browsershot/Puppeteer.
RUN apk add --no-cache \
        postgresql-dev \
        libzip-dev \
        icu-dev \
        oniguruma-dev \
        fcgi \
        shadow \
        texlive \
        texmf-dist-latex \
        texmf-dist-latexrecommended \
        texmf-dist-latexextra \
        texmf-dist-fontsrecommended \
        texmf-dist-fontsextra \
        texmf-dist-langitalian \
        texmf-dist-plaingeneric \
        texmf-dist-pictures \
        poppler-utils \
        chromium \
        font-freefont \
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
